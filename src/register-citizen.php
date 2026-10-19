<?php
// Handles POST requests from Citizen_Sign_Up.html.
// Expects: full_name, email, password, confirm_password, captcha (+ hidden "website" honeypot)
// Citizens are not tied to a barangay and need no approval: the account works immediately.
// A citizen is identified by full name (display) and email (what they sign in with). The
// username column is still required and unique, so it simply stores the email address.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';

function fail(string $message, bool $captchaFailed = false): void
{
    $out = ['success' => false, 'message' => $message];
    if ($captchaFailed) {
        $out['captcha_failed'] = true;   // tells the page to load a fresh code
    }
    echo json_encode($out);
    exit;
}

// Honeypot: humans never see this field; bots tend to fill it in.
if (trim($_POST['website'] ?? '') !== '') {
    echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
    exit;
}

$fullName        = trim(preg_replace('/\s+/u', ' ', $_POST['full_name'] ?? ''));
$email           = trim($_POST['email'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
    fail('All fields are required.', true);
}

// The captcha is consumed by this check, so every failure after it asks for a new code
if (!captcha_check($_POST['captcha'] ?? '')) {
    fail('The verification code was incorrect. Please try again with the new code.', true);
}

if (!preg_match("/^\p{L}[\p{L}\p{M} .'-]{1,99}$/u", $fullName)) {
    fail('Please enter your full name (letters, spaces, dots, apostrophes and dashes only).', true);
}

// Any normal email is fine for citizens (no government domain needed)
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    fail('Please enter a valid email address.', true);
}
$email = strtolower($email);

if ($password !== $confirmPassword) {
    fail('The two passwords do not match.', true);
}
if (strlen($password) < 8) {
    fail('Password must be at least 8 characters.', true);
}

try {
    $stmt = $pdo->prepare("SELECT user_id FROM tbl_users WHERE username = ? OR email = ?");
    $stmt->execute([$email, $email]);
    if ($stmt->fetch()) {
        fail('An account with that email already exists. Try signing in instead.', true);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO tbl_users (barangay_id, username, full_name, email, password_hash, role)
         VALUES (NULL, ?, ?, ?, ?, 'citizen')"
    );
    $stmt->execute([$email, $fullName, $email, password_hash($password, PASSWORD_DEFAULT)]);

    echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
} catch (PDOException $e) {
    http_response_code(500);
    fail('Something went wrong. Please try again.', true);
    // error_log($e->getMessage());
}
