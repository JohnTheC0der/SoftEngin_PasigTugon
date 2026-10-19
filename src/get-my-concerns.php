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

const RESPONSE_HOURS = ['High' => 36, 'Medium' => 72, 'Low' => 168];

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
        $hours = RESPONSE_HOURS[$row['priority_level']] ?? 168;
        $submitted = (int) $row['submitted_ts'];
        return [
            'case_number'       => (int) $row['concern_id'],   // the concern_id is the case number
            'barangay'          => $row['barangay_name'],
            'text'              => $row['raw_text'],
            'sector'            => $row['sector'],
            'priority'          => $row['priority_level'],
            'status'            => $row['status'],             // 'ongoing' | 'archived'
            'submitted_ts'      => $submitted,                 // unix seconds; the browser formats the date
            'response_hours'    => $hours,
            // seconds left (negative = overdue); null once staff have archived the concern
            'remaining_seconds' => $row['status'] === 'archived'
                ? null
                : $submitted + $hours * 3600 - (int) $row['now_ts'],
        ];
    }, $stmt->fetchAll());

    echo json_encode(['concerns' => $concerns, 'response_hours' => RESPONSE_HOURS]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading your concerns.']);
    // error_log($e->getMessage());
}
