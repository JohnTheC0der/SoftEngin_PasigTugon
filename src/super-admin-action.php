<?php
// super-admin-action.php
// Handles all super admin write actions:
//   approve, reject, archive, restore, create_super_admin
// POST params: action, user_id (for most), username/email/password (for create)

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    $response['message'] = 'Not authorized.';
    echo json_encode($response);
    exit;
}

$action = trim($_POST['action'] ?? '');

// ──────────────────────────────────────────────────────────────
//  APPROVE
// ──────────────────────────────────────────────────────────────
if ($action === 'approve') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    if (!$userId) {
        $response['message'] = 'Invalid user.';
        echo json_encode($response);
        exit;
    }

    try {
        // The approval status lives on tbl_barangay; update through the user's barangay_id
        $stmt = $pdo->prepare(
            "UPDATE tbl_barangay b
             JOIN tbl_users u ON u.barangay_id = b.barangay_id
             SET b.approval_status = 'approved', b.approved_at = NOW()
             WHERE u.user_id = ? AND u.role = 'admin'"
        );
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            $response['message'] = 'No matching admin account found.';
        } else {
            $response['success'] = true;
            $response['message'] = 'Barangay approved.';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Database error.';
        // error_log($e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────
    //  REJECT
    // ──────────────────────────────────────────────────────────────
} elseif ($action === 'reject') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    if (!$userId) {
        $response['message'] = 'Invalid user.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE tbl_barangay b
             JOIN tbl_users u ON u.barangay_id = b.barangay_id
             SET b.approval_status = 'rejected'
             WHERE u.user_id = ? AND u.role = 'admin'"
        );
        $stmt->execute([$userId]);

        $response['success'] = true;
        $response['message'] = 'Account rejected.';
    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Database error.';
    }

    // ──────────────────────────────────────────────────────────────
    //  ARCHIVE  (deactivate account, approval_status → pending so the
    //            barangay slot opens for a new admin to register)
    // ──────────────────────────────────────────────────────────────
} elseif ($action === 'archive') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    if (!$userId) {
        $response['message'] = 'Invalid user.';
        echo json_encode($response);
        exit;
    }

    // Guard: super admins cannot archive themselves
    if ($userId === (int) $_SESSION['user_id']) {
        $response['message'] = 'You cannot archive your own account.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Deactivate the user
        $stmt = $pdo->prepare("UPDATE tbl_users SET is_active = FALSE WHERE user_id = ?");
        $stmt->execute([$userId]);

        // If this was a barangay admin, reset the barangay approval to 'pending'
        // so a new admin can register for the same barangay later
        $stmt = $pdo->prepare(
            "UPDATE tbl_barangay b
             JOIN tbl_users u ON u.barangay_id = b.barangay_id
             SET b.approval_status = 'pending', b.approved_at = NULL
             WHERE u.user_id = ? AND u.role = 'admin'"
        );
        $stmt->execute([$userId]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = 'Account archived.';
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        $response['message'] = 'Database error.';
        // error_log($e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────
    //  RESTORE
    // ──────────────────────────────────────────────────────────────
} elseif ($action === 'restore') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    if (!$userId) {
        $response['message'] = 'Invalid user.';
        echo json_encode($response);
        exit;
    }

    try {
        // Find which barangay this archived admin belongs to
        $stmt = $pdo->prepare("SELECT barangay_id FROM tbl_users WHERE user_id = ? AND role = 'admin'");
        $stmt->execute([$userId]);
        $barangayId = $stmt->fetchColumn();

        if ($barangayId === false) {
            $response['message'] = 'Admin account not found.';
            echo json_encode($response);
            exit;
        }

        // Guard: refuse to restore if another active admin already occupies this barangay
        // (this can happen if a new admin registered for the same barangay after this one was archived)
        $stmt = $pdo->prepare(
            "SELECT user_id FROM tbl_users WHERE barangay_id = ? AND user_id != ? AND is_active = TRUE"
        );
        $stmt->execute([$barangayId, $userId]);
        if ($stmt->fetch()) {
            $response['message'] = 'This barangay already has an active admin. Archive or reject that account first.';
            echo json_encode($response);
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE tbl_users SET is_active = TRUE WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Fully reinstate — re-approve the barangay too, so the restored admin goes
        // straight back to their dashboard instead of being stuck on the wait page.
        $stmt = $pdo->prepare(
            "UPDATE tbl_barangay SET approval_status = 'approved', approved_at = NOW() WHERE barangay_id = ?"
        );
        $stmt->execute([$barangayId]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = 'Account restored and barangay re-approved.';
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        $response['message'] = 'Database error.';
    }

    // ──────────────────────────────────────────────────────────────
    //  CREATE SUPER ADMIN
    // ──────────────────────────────────────────────────────────────
} elseif ($action === 'create_super_admin') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address.';
        echo json_encode($response);
        exit;
    }

    // Same password rules as change-password.php
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[^A-Za-z0-9]/', $password)
    ) {
        $response['message'] = 'Password must be at least 8 characters with an uppercase letter, a number, and a symbol.';
        echo json_encode($response);
        exit;
    }

    try {
        // Reject duplicate username or email
        $stmt = $pdo->prepare("SELECT user_id FROM tbl_users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $response['message'] = 'That username or email is already taken.';
            echo json_encode($response);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        // barangay_id is NULL for super admins — they are not tied to any barangay
        $stmt = $pdo->prepare(
            "INSERT INTO tbl_users (barangay_id, username, email, password_hash, role)
             VALUES (NULL, ?, ?, ?, 'super_admin')"
        );
        $stmt->execute([$username, $email, $hash]);

        $response['success'] = true;
        $response['message'] = 'Super admin account created.';
    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Database error.';
        // error_log($e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────
    //  UNKNOWN ACTION
    // ──────────────────────────────────────────────────────────────
} else {
    $response['message'] = 'Unknown action.';
}

echo json_encode($response);
