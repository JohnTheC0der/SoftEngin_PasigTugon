<?php
// Changes the logged-in admin's own password.
// POST params: current_password, new_password, confirm_password

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    $response['message'] = 'Not authorized.';
    echo json_encode($response);
    exit;
}

$userId          = $_SESSION['user_id'];
$currentPassword = $_POST['current_password'] ?? '';
$newPassword     = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    $response['message'] = 'Please fill in all fields.';
    echo json_encode($response);
    exit;
}

if ($newPassword !== $confirmPassword) {
    $response['message'] = 'New passwords do not match.';
    echo json_encode($response);
    exit;
}

// Password rule: at least 8 characters, one uppercase letter, one number, one symbol.
// Checked server-side since this is the actual source of truth — the matching
// client-side check is just there to give the user feedback before they submit.
$meetsLength    = strlen($newPassword) >= 8;
$hasUppercase   = preg_match('/[A-Z]/', $newPassword);
$hasNumber      = preg_match('/[0-9]/', $newPassword);
$hasSymbol      = preg_match('/[^A-Za-z0-9]/', $newPassword);

if (!$meetsLength || !$hasUppercase || !$hasNumber || !$hasSymbol) {
    $response['message'] = 'Password must be at least 8 characters and include an uppercase letter, a number, and a symbol.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT password_hash FROM tbl_users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        $response['message'] = 'Current password is incorrect.';
        echo json_encode($response);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE tbl_users SET password_hash = ? WHERE user_id = ?");
    $stmt->execute([$newHash, $userId]);

    $response['success'] = true;
    $response['message'] = 'Password updated successfully.';
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong. Please try again.';
    // error_log($e->getMessage());
}

echo json_encode($response);
