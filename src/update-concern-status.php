<?php
// Archives or restores one or more concerns belonging to this staff member's barangay.
// POST params: ids[] (array of concern_id), action = 'archive' | 'restore'
// Staff only — super admins have a read-only view and can never reach this.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';

$response = ['success' => false, 'message' => ''];

try {
    $user = require_api_role($pdo, 'staff');
    $barangayId = $user['barangay_id'];

    $ids = $_POST['ids'] ?? [];
    $action = $_POST['action'] ?? '';

    if (!is_array($ids) || count($ids) === 0) {
        $response['message'] = 'No concerns were selected.';
        echo json_encode($response);
        exit;
    }

    if (!in_array($action, ['archive', 'restore'], true)) {
        $response['message'] = 'Invalid action.';
        echo json_encode($response);
        exit;
    }

    // Sanitize ids to integers only
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (count($ids) === 0) {
        $response['message'] = 'No valid concerns were selected.';
        echo json_encode($response);
        exit;
    }

    $newStatus = $action === 'archive' ? 'archived' : 'ongoing';

    // Scoped to this staff member's own barangay_id, so one barangay can never
    // touch another barangay's concerns, even by guessing IDs.
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "UPDATE tbl_concerns
         SET status = ?
         WHERE barangay_id = ? AND concern_id IN ($placeholders)"
    );
    $stmt->execute(array_merge([$newStatus, $barangayId], $ids));

    $response['success'] = true;
    $response['updated'] = $stmt->rowCount();
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong updating the concern status.';
    // error_log($e->getMessage());
}

echo json_encode($response);
