<?php
// Archives or restores one or more concerns belonging to this admin's barangay.
// POST params: ids[] (array of concern_id), action = 'archive' | 'restore'

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || empty($_SESSION['barangay_id'])) {
    http_response_code(403);
    $response['message'] = 'Not authorized.';
    echo json_encode($response);
    exit;
}

$barangayId = $_SESSION['barangay_id'];
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

// Restoring a concern that never got a sector/priority sends it back to 'for_review',
// not 'ongoing' (it still needs the manual review step).
$statusExpr = $action === 'archive'
    ? "'archived'"
    : "IF(sector IS NULL OR priority_level IS NULL, 'for_review', 'ongoing')";

// Sanitize ids to integers only
$ids = array_filter(array_map('intval', $ids));
if (count($ids) === 0) {
    $response['message'] = 'No valid concerns were selected.';
    echo json_encode($response);
    exit;
}

try {
    // Scoped to this admin's own barangay_id, so one barangay's admin can never
    // touch another barangay's concerns, even by guessing IDs.
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "UPDATE tbl_concerns
         SET status = $statusExpr
         WHERE barangay_id = ? AND concern_id IN ($placeholders)"
    );
    $stmt->execute(array_merge([$barangayId], $ids));

    $response['success'] = true;
    $response['updated'] = $stmt->rowCount();
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong updating the concern status.';
    // error_log($e->getMessage());
}

echo json_encode($response);
