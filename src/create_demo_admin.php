<?php
// Creates or refreshes the local demo barangay-admin account for UI testing.
// Run from the command line only; do not use on a production database.
//
// Usage:
//   php create_demo_admin.php
//
// Demo login:
//   Username: demo_admin
//   Password: DemoAdmin!2026

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/db_connect.php';

if (!in_array(strtolower(DB_HOST), ['localhost', '127.0.0.1'], true) || DB_NAME !== 'pasig_tugon') {
    die("Error: demo accounts can only be created in the local pasig_tugon database.\n");
}

$username = 'demo_admin';
$email = 'demo.admin@pasigcity.gov.ph';
$passwordHash = password_hash('DemoAdmin!2026', PASSWORD_DEFAULT);
$barangayName = 'Demo Barangay';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT user_id FROM tbl_users WHERE username = ? OR email = ? FOR UPDATE"
    );
    $stmt->execute([$username, $email]);
    $accounts = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($accounts) > 1) {
        throw new RuntimeException('The demo username and email belong to different accounts.');
    }

    $stmt = $pdo->prepare(
        "SELECT barangay_id FROM tbl_barangay WHERE barangay_name = ? FOR UPDATE"
    );
    $stmt->execute([$barangayName]);
    $barangayId = $stmt->fetchColumn();

    if ($barangayId === false) {
        $stmt = $pdo->prepare(
            "INSERT INTO tbl_barangay (barangay_name, approval_status) VALUES (?, 'approved')"
        );
        $stmt->execute([$barangayName]);
        $barangayId = $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare(
            "SELECT user_id FROM tbl_users WHERE barangay_id = ? AND user_id <> ? FOR UPDATE"
        );
        $stmt->execute([$barangayId, $accounts[0] ?? 0]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Demo Barangay is already assigned to another admin account.');
        }

        $stmt = $pdo->prepare(
            "UPDATE tbl_barangay SET approval_status = 'approved' WHERE barangay_id = ?"
        );
        $stmt->execute([$barangayId]);
    }

    if ($accounts) {
        $stmt = $pdo->prepare(
            "UPDATE tbl_users
             SET barangay_id = ?, username = ?, email = ?, password_hash = ?, role = 'admin', is_active = TRUE
             WHERE user_id = ?"
        );
        $stmt->execute([$barangayId, $username, $email, $passwordHash, $accounts[0]]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tbl_users (barangay_id, username, email, password_hash, role, is_active)
             VALUES (?, ?, ?, ?, 'admin', TRUE)"
        );
        $stmt->execute([$barangayId, $username, $email, $passwordHash]);
    }

    $pdo->commit();
    echo "Local demo admin is ready.\n";
    echo "Username: $username\n";
    echo "Password: DemoAdmin!2026\n";
} catch (PDOException | RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Error creating demo admin: " . $e->getMessage() . "\n");
    exit(1);
}
