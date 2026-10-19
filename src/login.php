<?php
// Handles POST requests from Sign_In.html — one sign-in for citizens, staff and super admins.
// Expects: username (the field carries the person's EMAIL; an old-style username still works), password
// Answers with the page to open next (relative to /public).

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';

$response = ['success' => false, 'message' => ''];

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $response['message'] = 'Please enter your email and password.';
    echo json_encode($response);
    exit;
}

try {
    // LEFT JOIN so citizens and super_admins, who have no barangay, still match
    $stmt = $pdo->prepare(
        "SELECT u.user_id, u.password_hash, u.is_active, u.barangay_id, u.role, b.approval_status
         FROM tbl_users u
         LEFT JOIN tbl_barangay b ON u.barangay_id = b.barangay_id
         WHERE u.username = ? OR LOWER(u.email) = LOWER(?)"
    );
    // Everyone signs in with their email. A username is still accepted so existing accounts
    // (e.g. the demo staff or a super admin made from the command line) keep working.
    // Usernames can never contain '@', so the two can't collide.
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    // Same generic message whether the username doesn't exist or the password is
    // wrong — this avoids telling an attacker which usernames are valid.
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $response['message'] = 'Invalid email or password.';
        echo json_encode($response);
        exit;
    }

    if (!$user['is_active']) {
        $response['message'] = 'This account has been deactivated. Please contact support.';
        echo json_encode($response);
        exit;
    }

    session_regenerate_id(true);   // new session id on every sign-in (prevents session fixation)
    $_SESSION['user_id']     = $user['user_id'];
    $_SESSION['barangay_id'] = $user['barangay_id'];   // null for citizen and super_admin
    $_SESSION['role']        = $user['role'];

    $stmt = $pdo->prepare("UPDATE tbl_users SET last_login_at = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);

    $response['success']  = true;
    $response['role']     = $user['role'];
    $response['redirect'] = home_page_for($user['role'], $user['approval_status'] === 'approved');
} catch (PDOException $e) {
    $response['message'] = 'Something went wrong. Please try again.';
    // error_log($e->getMessage()); // uncomment while debugging locally
}

echo json_encode($response);
