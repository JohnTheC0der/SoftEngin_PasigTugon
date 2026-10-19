<?php
// Read-only list of EVERY concern from every barangay (ongoing and archived) for the
// Super Admin "Concerns" tab. Filtering by status / barangay / sector / priority / search
// happens in the browser. This endpoint only reads — there is deliberately no way for a
// super admin to archive, restore or re-classify a concern; that is the barangay staff's job.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'timing.php';

try {
    require_api_role($pdo, 'super_admin');

    $rows = $pdo->query(
        "SELECT c.concern_id, c.barangay_id, b.barangay_name, c.raw_text, c.extracted_keywords,
                c.sector, c.priority_level, c.confidence_score, c.status, c.submitted_at,
                UNIX_TIMESTAMP(c.submitted_at) AS submitted_ts, UNIX_TIMESTAMP() AS now_ts,
                u.full_name AS citizen_name, u.email AS citizen_email
         FROM tbl_concerns c
         JOIN tbl_barangay b ON b.barangay_id = c.barangay_id
         LEFT JOIN tbl_users u ON u.user_id = c.user_id
         ORDER BY c.submitted_at DESC, c.concern_id DESC"
    )->fetchAll();

    $concerns = array_map(function ($row) {
        return array_merge(concern_citizen($row), concern_timing($row), [
            'id'          => (int) $row['concern_id'],      // Case Number
            'barangay_id' => (int) $row['barangay_id'],
            'barangay'    => $row['barangay_name'],
            'date'        => date('n/j/Y', strtotime($row['submitted_at'])),
            'status'      => $row['status'],
            'text'        => $row['raw_text'],
            'keywords'    => $row['extracted_keywords'] ?? '',
            'sector'      => $row['sector'],
            'priority'    => $row['priority_level'],
            'confidence'  => $row['confidence_score'] === null ? null : (float) $row['confidence_score'],
        ]);
    }, $rows);

    echo json_encode(['concerns' => $concerns]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading concerns.']);
    // error_log($e->getMessage());
}
