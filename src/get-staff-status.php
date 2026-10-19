<?php
// Called by Staff_Wait_Page.html to check whether this staff member's barangay
// has been approved yet. Relies on the session set during login.php.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';

try {
    $user = auth_user($pdo);

    if ($user === null || $user['role'] !== 'staff') {
        echo json_encode(['status' => 'pending', 'barangayUrl' => '#']);
        exit;
    }

    $ready = $user['approval_status'] === 'approved';
    echo json_encode([
        'status'      => $ready ? 'ready' : 'pending',
        'barangayUrl' => $ready ? 'Staff_Dashboard_Page.php' : '#'
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'pending', 'barangayUrl' => '#']);
}
