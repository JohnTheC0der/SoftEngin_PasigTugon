<?php
// Super admin backup actions.
// POST: action = 'create' | 'save_interval' | 'restore'
//   save_interval: interval_days (1, 2, 3, 5 or 7)
//   restore:       password (the super admin's own password, as confirmation)

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'backup-lib.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    $response['message'] = 'Not authorized.';
    echo json_encode($response);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    $backup = new PasigBackup($pdo);

    if ($action === 'create') {
        $b = $backup->create('manual', $userId);
        $response['success'] = true;
        $response['message'] = 'Backup created (' . max(1, round($b['size'] / 1024)) . ' KB).';

    } elseif ($action === 'save_interval') {
        $backup->saveInterval((int) ($_POST['interval_days'] ?? 0));
        $response['success'] = true;
        $response['message'] = 'Backup interval saved.';

    } elseif ($action === 'restore') {
        // Confirm identity: restoring replaces live data, so ask for the password again
        $stmt = $pdo->prepare("SELECT password_hash FROM tbl_users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($_POST['password'] ?? '', $hash)) {
            $response['message'] = 'Incorrect password.';
            echo json_encode($response);
            exit;
        }

        $last = $backup->last();
        if (!$last) {
            $response['message'] = 'There is no backup to restore yet.';
            echo json_encode($response);
            exit;
        }
        $file = $backup->filePath($last);
        if (!is_file($file)) {
            $response['message'] = 'The backup file is missing from the server.';
            echo json_encode($response);
            exit;
        }

        // Safety net: snapshot the current data first so a failed restore can be rolled back
        $safety = $backup->create('pre_restore', $userId);
        try {
            $backup->restoreFile($file);
        } catch (Throwable $e) {
            try {
                $backup->restoreFile($backup->dir() . '/' . $safety['file']);
                $response['message'] = 'Restore failed, so your data was rolled back to how it was.';
            } catch (Throwable $e2) {
                $response['message'] = 'Restore failed and the rollback also failed. Do not use the system; contact the developer. Safety copy: ' . $safety['file'];
            }
            echo json_encode($response);
            exit;
        }

        // The restored users table may not match this session: sign everyone out of it
        $_SESSION = [];
        session_destroy();
        $response['success'] = true;
        $response['logout']  = true;
        $response['message'] = 'Backup restored. Please log in again.';

    } else {
        $response['message'] = 'Unknown action.';
    }
} catch (InvalidArgumentException $e) {
    $response['message'] = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();   // e.g. "Backup failed: ..." (admin-only endpoint)
}

echo json_encode($response);
