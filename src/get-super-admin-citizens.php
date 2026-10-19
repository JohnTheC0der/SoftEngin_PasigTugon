<?php
// Read-only list of EVERY citizen account with their full concern history, for the
// Super Admin "Citizens" tab. Nothing here changes data: super admins can look but not edit.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'timing.php';

try {
    require_api_role($pdo, 'super_admin');

    $citizens = $pdo->query(
        "SELECT user_id, full_name, username, email, is_active, created_at, last_login_at
         FROM tbl_users
         WHERE role = 'citizen'
         ORDER BY created_at DESC, user_id DESC"
    )->fetchAll();

    $concernRows = $pdo->query(
        "SELECT c.concern_id, c.user_id, b.barangay_name, c.raw_text, c.sector, c.priority_level, c.status,
                UNIX_TIMESTAMP(c.submitted_at) AS submitted_ts, UNIX_TIMESTAMP() AS now_ts
         FROM tbl_concerns c
         JOIN tbl_barangay b ON b.barangay_id = c.barangay_id
         WHERE c.user_id IS NOT NULL
         ORDER BY c.submitted_at DESC, c.concern_id DESC"
    )->fetchAll();

    $byCitizen = [];
    foreach ($concernRows as $row) {
        $byCitizen[(int) $row['user_id']][] = array_merge(concern_timing($row), [
            'id'       => (int) $row['concern_id'],   // Case Number
            'barangay' => $row['barangay_name'],
            'text'     => $row['raw_text'],
            'sector'   => $row['sector'],
            'priority' => $row['priority_level'],
            'status'   => $row['status'],
        ]);
    }

    $out = array_map(function ($c) use ($byCitizen) {
        $history = $byCitizen[(int) $c['user_id']] ?? [];
        $ongoing = count(array_filter($history, fn($h) => $h['status'] === 'ongoing'));
        return [
            'user_id'        => (int) $c['user_id'],
            // citizens who registered before full names existed fall back to what they signed up with
            'name'           => $c['full_name'] ?: $c['username'],
            'email'          => $c['email'],
            'is_active'      => (bool) $c['is_active'],
            'created_at'     => $c['created_at'],
            'last_login_at'  => $c['last_login_at'],
            'total_concerns' => count($history),
            'ongoing'        => $ongoing,
            'archived'       => count($history) - $ongoing,
            'concerns'       => $history,
        ];
    }, $citizens);

    echo json_encode(['citizens' => $out]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading citizens.']);
    // error_log($e->getMessage());
}
