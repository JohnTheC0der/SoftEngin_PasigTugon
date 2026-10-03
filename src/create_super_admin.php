<?php
// One-time bootstrap script for creating a super_admin account.
// Run from the command line only — NOT accessible as a web page.
//
// Usage:
//   php create_super_admin.php <username> <email> <password>
//
// Example:
//   php create_super_admin.php superadmin super@pasigtugon.gov.ph MySecurePass123

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once 'db_connect.php';

if ($argc !== 4) {
    die("Usage: php create_super_admin.php <username> <email> <password>\n");
}

$username = trim($argv[1]);
$email    = trim($argv[2]);
$password = $argv[3];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: that is not a valid email address.\n");
}

if (strlen($password) < 8) {
    die("Error: password should be at least 8 characters.\n");
}

try {
    $stmt = $pdo->prepare("SELECT user_id FROM tbl_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        die("Error: that username or email is already taken.\n");
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // barangay_id is left NULL — super_admins are not tied to a barangay
    $stmt = $pdo->prepare(
        "INSERT INTO tbl_users (barangay_id, username, email, password_hash, role)
         VALUES (NULL, ?, ?, ?, 'super_admin')"
    );
    $stmt->execute([$username, $email, $passwordHash]);

    echo "Super admin account created successfully for '$username'.\n";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
