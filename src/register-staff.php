<?php
// Handles POST requests from Staff_Sign_Up.html (barangay staff registration).
// Expects: username, email, barangay (slug like "bagong-ilog"), password, confirm_password
// The account is created right away, but the barangay stays 'pending' until a
// super admin approves it — staff cannot open their dashboard before that.

header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';

$response = ['success' => false, 'message' => ''];

function fail(string $message): void
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$username        = trim($_POST['username'] ?? '');
$email           = trim($_POST['email'] ?? '');
$barangaySlug    = trim($_POST['barangay'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($username === '' || $email === '' || $barangaySlug === '' || $password === '' || $confirmPassword === '') {
    fail('All fields are required.');
}

// Only real barangays can be applied for (must match the dropdown in Staff_Sign_Up.html)
$validBarangays = ['bagong-ilog', 'bambang', 'caniogan', 'kapitolyo', 'malinao',
                   'oranbo', 'pinagbuhatan', 'san-antonio', 'san-joaquin', 'ugong'];
if (!in_array($barangaySlug, $validBarangays, true)) {
    fail('Please select a valid barangay.');
}

if ($problem = username_problem($username)) {
    fail($problem);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    fail('Please enter a valid email address.');
}

// Staff registration is restricted to official Pasig City government email addresses.
// Mirrors the browser check, but enforced here too since a direct POST would skip it.
if (!str_ends_with(strtolower($email), '@pasigcity.gov.ph')) {
    fail('Only official @pasigcity.gov.ph email addresses are permitted to create staff accounts.');
}

if ($password !== $confirmPassword) {
    fail('The two passwords do not match.');
}
if ($problem = staff_password_problem($password)) {
    fail($problem);
}

// The dropdown sends a slug like "bagong-ilog" — convert it back to "Bagong Ilog"
$barangayName = ucwords(str_replace('-', ' ', $barangaySlug));

try {
    // One staff account per barangay — reject if this barangay already has one
    $stmt = $pdo->prepare(
        "SELECT u.user_id FROM tbl_users u
         JOIN tbl_barangay b ON u.barangay_id = b.barangay_id
         WHERE b.barangay_name = ? AND u.role = 'staff' AND u.is_active = TRUE"
    );
    $stmt->execute([$barangayName]);
    if ($stmt->fetch()) {
        fail('This barangay already has a registered staff account.');
    }

    // Reject duplicate username/email (shared with citizens and super admins)
    $stmt = $pdo->prepare("SELECT user_id FROM tbl_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        fail('That username or email is already taken.');
    }

    $pdo->beginTransaction();

    // Reuse the barangay row if it already exists, otherwise create it as pending
    $stmt = $pdo->prepare("SELECT barangay_id FROM tbl_barangay WHERE barangay_name = ?");
    $stmt->execute([$barangayName]);
    $barangay = $stmt->fetch();

    if ($barangay) {
        $barangayId = $barangay['barangay_id'];
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tbl_barangay (barangay_name, approval_status) VALUES (?, 'pending')"
        );
        $stmt->execute([$barangayName]);
        $barangayId = $pdo->lastInsertId();
    }

    // The password is hashed here before it ever touches the database
    $stmt = $pdo->prepare(
        "INSERT INTO tbl_users (barangay_id, username, email, password_hash, role)
         VALUES (?, ?, ?, ?, 'staff')"
    );
    $stmt->execute([$barangayId, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Account created successfully.';
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Something went wrong. Please try again.';
    // error_log($e->getMessage()); // uncomment while debugging locally
}

echo json_encode($response);
