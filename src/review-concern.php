<?php
// Saves a manual sector + priority for a concern that was flagged 'for_review'
// (model confidence too low), then moves it to 'ongoing'.
// POST params: id, sector, priority

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

$id       = (int) ($_POST['id'] ?? 0);
$sector   = $_POST['sector'] ?? '';
$priority = $_POST['priority'] ?? '';

if (!$id
    || !in_array($sector, ['Health', 'Infrastructure', 'Crime', 'Environment'], true)
    || !in_array($priority, ['High', 'Medium', 'Low'], true)) {
    $response['message'] = 'Please choose both a sector and a priority level.';
    echo json_encode($response);
    exit;
}

try {
    // Scoped to this admin's barangay and only to concerns still awaiting review
    $stmt = $pdo->prepare(
        "UPDATE tbl_concerns
         SET sector = ?, priority_level = ?, status = 'ongoing'
         WHERE concern_id = ? AND barangay_id = ? AND status = 'for_review'"
    );
    $stmt->execute([$sector, $priority, $id, $_SESSION['barangay_id']]);

    if ($stmt->rowCount() === 0) {
        $response['message'] = 'This concern is no longer waiting for review.';
    } else {
        $response['success'] = true;
        $response['message'] = 'Saved.';
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong saving the review.';
    // error_log($e->getMessage());
}

echo json_encode($response);
