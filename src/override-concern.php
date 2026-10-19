<?php
// Lets barangay staff overturn the system's assessment: they can set any sector and
// priority they judge best, on any concern in their own barangay, at any time.
// POST params: id, sector, priority

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'classifier.php';   // CONCERN_SECTORS / CONCERN_PRIORITIES

try {
    $user = require_api_role($pdo, 'staff');

    $id       = (int) ($_POST['id'] ?? 0);
    $sector   = $_POST['sector'] ?? '';
    $priority = $_POST['priority'] ?? '';

    if (!$id || !in_array($sector, CONCERN_SECTORS, true) || !in_array($priority, CONCERN_PRIORITIES, true)) {
        echo json_encode(['success' => false, 'message' => 'Please choose both a sector and a priority level.']);
        exit;
    }

    // Scoped to this staff member's barangay, so another barangay's concern can't be touched by guessing ids.
    // (Checked with a SELECT first: MySQL reports 0 changed rows when the values are unchanged.)
    $stmt = $pdo->prepare("SELECT concern_id FROM tbl_concerns WHERE concern_id = ? AND barangay_id = ?");
    $stmt->execute([$id, $user['barangay_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Concern not found.']);
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE tbl_concerns SET sector = ?, priority_level = ? WHERE concern_id = ? AND barangay_id = ?"
    );
    $stmt->execute([$sector, $priority, $id, $user['barangay_id']]);

    echo json_encode(['success' => true, 'message' => 'Saved.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong saving your changes.']);
    // error_log($e->getMessage());
}
