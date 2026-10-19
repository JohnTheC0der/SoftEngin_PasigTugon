<?php
// Returns the concerns list for this staff member's barangay, filtered by status.
// GET param: filter = 'ongoing' (default) | 'archived' | 'all'
// Sector / priority / search filtering happens in the browser on the returned rows.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'timing.php';

try {
    $user = require_api_role($pdo, 'staff');
    $barangayId = $user['barangay_id'];
    $filter = $_GET['filter'] ?? 'ongoing';

    if (!in_array($filter, ['ongoing', 'archived', 'all'], true)) {
        $filter = 'ongoing';
    }

    $cols = "c.concern_id, c.raw_text, c.extracted_keywords, c.sector, c.priority_level,
             c.confidence_score, c.status, c.submitted_at,
             UNIX_TIMESTAMP(c.submitted_at) AS submitted_ts, UNIX_TIMESTAMP() AS now_ts,
             u.full_name AS citizen_name, u.email AS citizen_email";

    if ($filter === 'all') {
        $stmt = $pdo->prepare(
            "SELECT $cols FROM tbl_concerns c
             LEFT JOIN tbl_users u ON u.user_id = c.user_id
             WHERE c.barangay_id = ?
             ORDER BY c.submitted_at DESC, c.concern_id DESC"
        );
        $stmt->execute([$barangayId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT $cols FROM tbl_concerns c
             LEFT JOIN tbl_users u ON u.user_id = c.user_id
             WHERE c.barangay_id = ? AND c.status = ?
             ORDER BY c.submitted_at DESC, c.concern_id DESC"
        );
        $stmt->execute([$barangayId, $filter]);
    }

    $concerns = array_map(function ($row) {
        return array_merge(concern_citizen($row), concern_timing($row), [
            'id'         => (int) $row['concern_id'],     // also the Case Number
            'date'       => date('n/j/Y', strtotime($row['submitted_at'])),
            'status'     => $row['status'],               // 'ongoing' | 'archived'
            'text'       => $row['raw_text'],
            'keywords'   => $row['extracted_keywords'] ?? '',
            'sector'     => $row['sector'],
            'priority'   => $row['priority_level'],
            'confidence' => $row['confidence_score'] === null ? null : (float) $row['confidence_score']
        ]);
    }, $stmt->fetchAll());

    echo json_encode(['concerns' => $concerns]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading concerns.']);
    // error_log($e->getMessage());
}
