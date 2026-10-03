<?php
// Called by Admin_Wait_Page.html to check whether this admin's barangay
// has been approved yet. Relies on the session set during login.php.

session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'pending', 'barangayUrl' => '#']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT b.approval_status
         FROM tbl_barangay b
         JOIN tbl_users u ON u.barangay_id = b.barangay_id
         WHERE u.user_id = ?"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['status' => 'pending', 'barangayUrl' => '#']);
        exit;
    }

    $status = ($row['approval_status'] === 'approved') ? 'ready' : 'pending';

    echo json_encode([
        'status'      => $status,
        'barangayUrl' => $status === 'ready' ? 'Admin_Dashboard_Page.php' : '#'
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'pending', 'barangayUrl' => '#']);
}
