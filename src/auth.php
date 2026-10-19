<?php
// Shared access-control helpers for the three roles: citizen, staff, super_admin.
//
//   Pages   (public/*.php):  require_page_role($pdo, 'staff');
//   Endpoints (src/*.php):   $user = require_api_role($pdo, 'staff');
//
// The role, active flag and (for staff) barangay approval are re-read from the
// database on every request, so archiving an account or un-approving a barangay
// takes effect immediately instead of when the session happens to expire.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Where each role lands after signing in (relative to /public)
function home_page_for(string $role, bool $approved = true): string
{
    switch ($role) {
        case 'super_admin':
            return 'Super_Admin_Dashboard.php';
        case 'staff':
            return $approved ? 'Staff_Dashboard_Page.php' : 'Staff_Wait_Page.html';
        default:
            return 'Citizen_Dashboard_Page.php';
    }
}

// Returns the signed-in user's fresh DB row, or null if nobody valid is signed in.
function auth_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare(
        "SELECT u.user_id, u.role, u.is_active, u.barangay_id, b.approval_status
         FROM tbl_users u
         LEFT JOIN tbl_barangay b ON b.barangay_id = u.barangay_id
         WHERE u.user_id = ?"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if (!$row || !$row['is_active']) {
        session_unset();
        session_destroy();
        return null;
    }
    $_SESSION['role']        = $row['role'];
    $_SESSION['barangay_id'] = $row['barangay_id'];
    return $row;
}

// For HTML pages: redirect instead of showing anything the person shouldn't see.
function require_page_role(PDO $pdo, string $role): array
{
    $user = auth_user($pdo);
    if ($user === null) {
        header('Location: Sign_In.html');
        exit;
    }
    if ($user['role'] !== $role) {
        header('Location: ' . home_page_for($user['role'], $user['approval_status'] === 'approved'));
        exit;
    }
    // Staff only get in once the super admin has approved their barangay
    if ($role === 'staff' && $user['approval_status'] !== 'approved') {
        header('Location: Staff_Wait_Page.html');
        exit;
    }
    return $user;
}

// For JSON endpoints: answer 401/403 and stop. Both "error" and "message" are set
// because different pages read different keys.
function require_api_role(PDO $pdo, string $role): array
{
    $user = auth_user($pdo);
    $problem = null;
    $code = 403;

    if ($user === null) {
        $problem = 'Please sign in again.';
        $code = 401;
    } elseif ($user['role'] !== $role) {
        $problem = 'Not authorized.';
    } elseif ($role === 'staff' && ($user['approval_status'] !== 'approved' || empty($user['barangay_id']))) {
        $problem = 'Your barangay has not been approved yet.';
    }

    if ($problem !== null) {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $problem, 'message' => $problem]);
        exit;
    }
    return $user;
}

// Single-use CAPTCHA check shared by the citizen sign-up and the concern form.
// The stored code is removed whether or not the guess was right, so the page
// must request a fresh image after every attempt.
const CAPTCHA_MAX_AGE_SECONDS = 600;   // a captcha image expires after 10 minutes

function captcha_check(string $input): bool
{
    $expected = $_SESSION['captcha_code'] ?? '';
    $issuedAt = $_SESSION['captcha_time'] ?? 0;
    unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);

    $input = strtoupper(trim($input));
    return $expected !== '' && $input !== ''
        && hash_equals($expected, $input)
        && (time() - $issuedAt) <= CAPTCHA_MAX_AGE_SECONDS;
}

// Rules for usernames, shared by both sign-up endpoints. Returns an error message or null.
function username_problem(string $username): ?string
{
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        return 'Username must be 3-50 characters: letters, numbers, dots, dashes or underscores.';
    }
    return null;
}

// Staff passwords follow the same rule as Settings > Change Password.
function staff_password_problem(string $password): ?string
{
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Password must be at least 8 characters and include an uppercase letter, a number, and a symbol.';
    }
    return null;
}
