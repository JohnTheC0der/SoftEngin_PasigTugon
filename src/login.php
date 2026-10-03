<?php
// Handles POST requests from Admin_Log_In.html
// Expects: username, password

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $response['message'] = 'Please enter your username and password.';
    echo json_encode($response);
    exit;
}

try {
    // LEFT JOIN (not JOIN) so super_admins, who have no barangay_id, still match this query
    $stmt = $pdo->prepare(
        "SELECT u.user_id, u.password_hash, u.is_active, u.barangay_id, u.role, b.approval_status
         FROM tbl_users u
         LEFT JOIN tbl_barangay b ON u.barangay_id = b.barangay_id
         WHERE u.username = ?"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Same generic message whether the username doesn't exist or the password is
    // wrong — this avoids telling an attacker which usernames are valid.
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $response['message'] = 'Invalid username or password.';
        echo json_encode($response);
        exit;
    }

    if (!$user['is_active']) {
        $response['message'] = 'This account has been deactivated. Please contact support.';
        echo json_encode($response);
        exit;
    }

    $_SESSION['user_id']     = $user['user_id'];
    $_SESSION['barangay_id'] = $user['barangay_id']; // null for super_admin
    $_SESSION['role']        = $user['role'];

    $stmt = $pdo->prepare("UPDATE tbl_users SET last_login_at = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);

    $response['success'] = true;
    $response['role']    = $user['role'];

    // super_admins have no barangay, so there's nothing to "approve" — treat them as always approved
    $response['status'] = ($user['role'] === 'super_admin') ? 'approved' : $user['approval_status'];
} catch (PDOException $e) {
    $response['message'] = 'Something went wrong. Please try again.';
    // error_log($e->getMessage()); // uncomment while debugging locally
}

echo json_encode($response);
