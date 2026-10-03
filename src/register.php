<?php
// Handles POST requests from Admin_Sign_Up.html
// Expects: username, email, barangay (slug like "bagong-ilog"), password

header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

$username     = trim($_POST['username'] ?? '');
$email        = trim($_POST['email'] ?? '');
$barangaySlug = trim($_POST['barangay'] ?? '');
$password     = $_POST['password'] ?? '';

if ($username === '' || $email === '' || $barangaySlug === '' || $password === '') {
    $response['message'] = 'All fields are required.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}

// Registration is restricted to official Pasig City government email addresses.
// This mirrors the check already done in the browser, but enforced here too since
// a direct POST request to this endpoint would otherwise skip the frontend check entirely.
if (!str_ends_with(strtolower($email), '@pasigcity.gov.ph')) {
    $response['message'] = 'Only official @pasigcity.gov.ph email addresses are permitted to create admin accounts.';
    echo json_encode($response);
    exit;
}

// The dropdown sends a slug like "bagong-ilog" — convert it back to "Bagong Ilog"
// so it matches a readable barangay_name in the database.
$barangayName = ucwords(str_replace('-', ' ', $barangaySlug));

try {
    // One admin per barangay — reject if this barangay already has an account
    $stmt = $pdo->prepare(
        "SELECT u.user_id FROM tbl_users u
         JOIN tbl_barangay b ON u.barangay_id = b.barangay_id
         WHERE b.barangay_name = ?"
    );
    $stmt->execute([$barangayName]);
    if ($stmt->fetch()) {
        $response['message'] = 'This barangay already has a registered admin account.';
        echo json_encode($response);
        exit;
    }

    // Reject duplicate username/email
    $stmt = $pdo->prepare("SELECT user_id FROM tbl_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $response['message'] = 'That username or email is already taken.';
        echo json_encode($response);
        exit;
    }

    $pdo->beginTransaction();

    // Reuse the barangay row if it already exists (e.g. a prior failed attempt), otherwise create it
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

    // Create the admin account — the password was auto-generated in the browser,
    // we hash it here before it ever touches the database.
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO tbl_users (barangay_id, username, email, password_hash, role)
         VALUES (?, ?, ?, ?, 'admin')"
    );
    $stmt->execute([$barangayId, $username, $email, $passwordHash]);

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
