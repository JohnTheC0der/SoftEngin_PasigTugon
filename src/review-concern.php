<?php
// Saves a sector + priority chosen by the admin. Works for:
//   - 'for_review' concerns (model confidence too low): saving moves them to 'ongoing'
//   - 'ongoing' concerns (the model was confident): the admin overrides what the model decided
// Archived concerns can't be edited.
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
    // Scoped to this admin's barangay. Only for_review and ongoing concerns can be edited
    // (archived ones can't). Saving always leaves the concern 'ongoing'.
    $stmt = $pdo->prepare(
        "UPDATE tbl_concerns
         SET sector = ?, priority_level = ?, status = 'ongoing'
         WHERE concern_id = ? AND barangay_id = ? AND status IN ('for_review', 'ongoing')"
    );
    $stmt->execute([$sector, $priority, $id, $_SESSION['barangay_id']]);

    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Saved.';
    } else {
        // MySQL reports 0 changed rows when the admin saves the same values that are already
        // stored, so check that the concern is still editable before calling it an error.
        $stmt = $pdo->prepare(
            "SELECT 1 FROM tbl_concerns
             WHERE concern_id = ? AND barangay_id = ? AND status IN ('for_review', 'ongoing')"
        );
        $stmt->execute([$id, $_SESSION['barangay_id']]);

        if ($stmt->fetchColumn() === false) {
            $response['message'] = 'This concern can no longer be edited.';
        } else {
            $response['success'] = true;
            $response['message'] = 'Saved.';
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong saving the review.';
    // error_log($e->getMessage());
}

echo json_encode($response);