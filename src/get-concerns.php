<?php
// Returns the concerns list for this admin's barangay, filtered by status.
// GET param: filter = 'ongoing' (default) | 'for_review' | 'archived' | 'all'
// Sector / priority / search filtering happens in the browser on the returned rows.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['barangay_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No barangay associated with this account.']);
    exit;
}

$barangayId = $_SESSION['barangay_id'];
$filter = $_GET['filter'] ?? 'ongoing';

if (!in_array($filter, ['ongoing', 'for_review', 'archived', 'all'], true)) {
    $filter = 'ongoing';
}

try {
    $cols = "concern_id, raw_text, extracted_keywords, sector, priority_level,
             confidence_score, status, submitted_at";

    if ($filter === 'all') {
        $stmt = $pdo->prepare(
            "SELECT $cols FROM tbl_concerns
             WHERE barangay_id = ?
             ORDER BY submitted_at DESC"
        );
        $stmt->execute([$barangayId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT $cols FROM tbl_concerns
             WHERE barangay_id = ? AND status = ?
             ORDER BY submitted_at DESC"
        );
        $stmt->execute([$barangayId, $filter]);
    }

    $concerns = array_map(function ($row) {
        return [
            'id'         => (int) $row['concern_id'],
            'date'       => date('n/j/Y', strtotime($row['submitted_at'])),
            'status'     => $row['status'],              // 'ongoing' | 'for_review' | 'archived'
            'text'       => $row['raw_text'],
            'keywords'   => $row['extracted_keywords'] ?? '',
            'sector'     => $row['sector'],               // null while for_review
            'priority'   => $row['priority_level'],       // null while for_review
            'confidence' => $row['confidence_score'] === null ? null : (float) $row['confidence_score']
        ];
    }, $stmt->fetchAll());

    // How many concerns are waiting for manual review (any filter) — drives the banner
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_concerns WHERE barangay_id = ? AND status = 'for_review'");
    $stmt->execute([$barangayId]);

    echo json_encode(['concerns' => $concerns, 'review_count' => (int) $stmt->fetchColumn()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading concerns.']);
    // error_log($e->getMessage());
}
