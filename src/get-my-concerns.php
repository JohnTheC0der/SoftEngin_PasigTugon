<?php
// Returns the signed-in citizen's own concerns for the Citizen Dashboard.
//
// Expected Response Time = submitted_at + a window that depends on the CURRENT priority
// (High 36 h, Medium 72 h, Low 168 h). It is calculated here, with the database's own
// clock for both "submitted" and "now", so the countdown is right even if the visitor's
// computer clock is off or PHP and MySQL use different time zones. The page ticks it
// down locally from `remaining_seconds`.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'timing.php';

try {
    $user = require_api_role($pdo, 'citizen');

    $stmt = $pdo->prepare(
        "SELECT c.concern_id, c.raw_text, c.sector, c.priority_level, c.status,
                UNIX_TIMESTAMP(c.submitted_at) AS submitted_ts, UNIX_TIMESTAMP() AS now_ts,
                b.barangay_name
         FROM tbl_concerns c
         JOIN tbl_barangay b ON b.barangay_id = c.barangay_id
         WHERE c.user_id = ?
         ORDER BY c.submitted_at DESC, c.concern_id DESC"
    );
    $stmt->execute([$user['user_id']]);

    $concerns = array_map(function ($row) {
        return array_merge([
            'case_number' => (int) $row['concern_id'],   // the concern_id is the case number
            'barangay'    => $row['barangay_name'],
            'text'        => $row['raw_text'],
            'sector'      => $row['sector'],
            'priority'    => $row['priority_level'],
            'status'      => $row['status'],             // 'ongoing' | 'archived'
        ], concern_timing($row));
    }, $stmt->fetchAll());

    $nameStmt = $pdo->prepare("SELECT full_name FROM tbl_users WHERE user_id = ?");
    $nameStmt->execute([$user['user_id']]);

    echo json_encode([
        'citizen_name'   => $nameStmt->fetchColumn() ?: null,
        'concerns'       => $concerns,
        'response_hours' => RESPONSE_HOURS,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading your concerns.']);
    // error_log($e->getMessage());
}
