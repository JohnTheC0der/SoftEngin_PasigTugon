<?php
// super-admin-get-data.php
// Returns all barangay admins and all super admins for the Super Admin panel.
// Only callable by an active super_admin session.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized.']);
    exit;
}

try {
    // All barangay admins with their barangay approval status
    $stmt = $pdo->query(
        "SELECT
            u.user_id,
            u.username,
            u.email,
            u.is_active,
            u.created_at,
            u.last_login_at,
            b.barangay_name,
            b.approval_status
         FROM tbl_users u
         JOIN tbl_barangay b ON u.barangay_id = b.barangay_id
         WHERE u.role = 'admin'
         ORDER BY b.approval_status = 'pending' DESC, u.created_at DESC"
    );
    $barangayAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast is_active to bool for clean JSON
    foreach ($barangayAdmins as &$row) {
        $row['is_active'] = (bool) $row['is_active'];
    }
    unset($row);

    // All super admins
    $stmt = $pdo->query(
        "SELECT user_id, username, email, is_active, created_at, last_login_at
         FROM tbl_users
         WHERE role = 'super_admin'
         ORDER BY created_at ASC"
    );
    $superAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($superAdmins as &$row) {
        $row['is_active'] = (bool) $row['is_active'];
    }
    unset($row);

    echo json_encode([
        'barangay_admins' => $barangayAdmins,
        'super_admins'    => $superAdmins,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load data.']);
    // error_log($e->getMessage());
}
