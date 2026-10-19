<?php
// Temporary testing helper — approves a barangay by ID so you can test the
// staff sign-in/wait-page flow without building the real super_admin approval
// UI yet. CLI only, same safety pattern as create_super_admin.php.
//
// Usage:
//   php approve_barangay.php <barangay_id>
//
// Example:
//   php approve_barangay.php 2

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once 'db_connect.php';

if ($argc !== 2 || !ctype_digit($argv[1])) {
    die("Usage: php approve_barangay.php <barangay_id>\n");
}

$barangayId = (int) $argv[1];

try {
    $stmt = $pdo->prepare("SELECT barangay_name, approval_status FROM tbl_barangay WHERE barangay_id = ?");
    $stmt->execute([$barangayId]);
    $barangay = $stmt->fetch();

    if (!$barangay) {
        die("No barangay found with ID $barangayId.\n");
    }

    $stmt = $pdo->prepare(
        "UPDATE tbl_barangay SET approval_status = 'approved', approved_at = NOW() WHERE barangay_id = ?"
    );
    $stmt->execute([$barangayId]);

    echo "Approved '{$barangay['barangay_name']}' (was: {$barangay['approval_status']}).\n";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
