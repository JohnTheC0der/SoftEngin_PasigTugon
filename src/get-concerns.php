<?php
// Returns the concerns list for this staff member's barangay, filtered by status.
// GET param: filter = 'ongoing' (default) | 'archived' | 'all'
// Sector / priority / search filtering happens in the browser on the returned rows.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';

try {
    $user = require_api_role($pdo, 'staff');
    $barangayId = $user['barangay_id'];
    $filter = $_GET['filter'] ?? 'ongoing';

    if (!in_array($filter, ['ongoing', 'archived', 'all'], true)) {
        $filter = 'ongoing';
    }

    $cols = "concern_id, raw_text, extracted_keywords, sector, priority_level,
             confidence_score, status, submitted_at";

    if ($filter === 'all') {
        $stmt = $pdo->prepare(
            "SELECT $cols FROM tbl_concerns
             WHERE barangay_id = ?
             ORDER BY submitted_at DESC, concern_id DESC"
        );
        $stmt->execute([$barangayId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT $cols FROM tbl_concerns
             WHERE barangay_id = ? AND status = ?
             ORDER BY submitted_at DESC, concern_id DESC"
        );
        $stmt->execute([$barangayId, $filter]);
    }

    $concerns = array_map(function ($row) {
        return [
            'id'         => (int) $row['concern_id'],     // also the Case Number
            'date'       => date('n/j/Y', strtotime($row['submitted_at'])),
            'status'     => $row['status'],               // 'ongoing' | 'archived'
            'text'       => $row['raw_text'],
            'keywords'   => $row['extracted_keywords'] ?? '',
            'sector'     => $row['sector'],
            'priority'   => $row['priority_level'],
            'confidence' => $row['confidence_score'] === null ? null : (float) $row['confidence_score']
        ];
    }, $stmt->fetchAll());

    echo json_encode(['concerns' => $concerns]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading concerns.']);
    // error_log($e->getMessage());
}
