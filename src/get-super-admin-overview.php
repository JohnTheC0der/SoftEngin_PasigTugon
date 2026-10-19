<?php
// Read-only, all-barangay version of the staff dashboard data, for the Super Admin dashboard.
// Only SELECT queries here — a super admin watches the concerns but never changes them
// (archiving and re-classifying belong to each barangay's staff).

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';

try {
    require_api_role($pdo, 'super_admin');

    $response = [];

    // --- Headline numbers ---
    $row = $pdo->query(
        "SELECT
            COALESCE(SUM(CASE WHEN status = 'ongoing'  THEN 1 ELSE 0 END), 0) AS active,
            COALESCE(SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END), 0) AS archived,
            COALESCE(SUM(CASE WHEN status = 'ongoing' AND priority_level = 'High' THEN 1 ELSE 0 END), 0) AS high
         FROM tbl_concerns"
    )->fetch();
    $response['total_active']   = (int) $row['active'];
    $response['total_archived'] = (int) $row['archived'];
    $response['total_high']     = (int) $row['high'];

    // --- Sector ranking & priority ranking (active concerns only), biggest first ---
    $sectorRows = $pdo->query(
        "SELECT sector, COUNT(*) AS total FROM tbl_concerns
         WHERE status = 'ongoing' GROUP BY sector ORDER BY total DESC, sector ASC"
    )->fetchAll();
    $response['sector_ranking'] = array_map(fn($r) => ['label' => $r['sector'], 'total' => (int) $r['total']], $sectorRows);

    $priorityRows = $pdo->query(
        "SELECT priority_level, COUNT(*) AS total FROM tbl_concerns
         WHERE status = 'ongoing' GROUP BY priority_level ORDER BY total DESC, priority_level ASC"
    )->fetchAll();
    $response['priority_ranking'] = array_map(fn($r) => ['label' => $r['priority_level'], 'total' => (int) $r['total']], $priorityRows);

    // --- Per-barangay breakdown (every approved barangay, plus any that has concerns) ---
    $barangayRows = $pdo->query(
        "SELECT b.barangay_id, b.barangay_name,
            COALESCE(SUM(CASE WHEN c.status = 'ongoing'  THEN 1 ELSE 0 END), 0) AS active,
            COALESCE(SUM(CASE WHEN c.status = 'archived' THEN 1 ELSE 0 END), 0) AS archived,
            COALESCE(SUM(CASE WHEN c.status = 'ongoing' AND c.priority_level = 'High'   THEN 1 ELSE 0 END), 0) AS p_high,
            COALESCE(SUM(CASE WHEN c.status = 'ongoing' AND c.priority_level = 'Medium' THEN 1 ELSE 0 END), 0) AS p_medium,
            COALESCE(SUM(CASE WHEN c.status = 'ongoing' AND c.priority_level = 'Low'    THEN 1 ELSE 0 END), 0) AS p_low,
            COALESCE(SUM(CASE WHEN c.status = 'ongoing' AND c.sector = 'Health'         THEN 1 ELSE 0 END), 0) AS s_health,
            COALESCE(SUM(CASE WHEN c.status = 'ongoing' AND c.sector = 'Infrastructure' THEN 1 ELSE 0 END), 0) AS s_infrastructure,
            COALESCE(SUM(CASE WHEN c.status = 'ongoing' AND c.sector = 'Crime'          THEN 1 ELSE 0 END), 0) AS s_crime,
            COALESCE(SUM(CASE WHEN c.status = 'ongoing' AND c.sector = 'Environment'    THEN 1 ELSE 0 END), 0) AS s_environment
         FROM tbl_barangay b
         LEFT JOIN tbl_concerns c ON c.barangay_id = b.barangay_id
         WHERE b.approval_status = 'approved'
            OR b.barangay_id IN (SELECT DISTINCT barangay_id FROM tbl_concerns)
         GROUP BY b.barangay_id, b.barangay_name
         ORDER BY active DESC, b.barangay_name ASC"
    )->fetchAll();
    $response['barangays'] = array_map(function ($r) {
        return [
            'id'       => (int) $r['barangay_id'],
            'name'     => $r['barangay_name'],
            'active'   => (int) $r['active'],
            'archived' => (int) $r['archived'],
            'priority' => ['High' => (int) $r['p_high'], 'Medium' => (int) $r['p_medium'], 'Low' => (int) $r['p_low']],
            'sector'   => [
                'Health'         => (int) $r['s_health'],
                'Infrastructure' => (int) $r['s_infrastructure'],
                'Crime'          => (int) $r['s_crime'],
                'Environment'    => (int) $r['s_environment'],
            ],
        ];
    }, $barangayRows);

    // --- Concerns received per day, last 7 days (all statuses). "ago" is computed by the
    //     database so it lines up with the stored timestamps. ---
    $stmt = $pdo->query(
        "SELECT DATEDIFF(CURDATE(), DATE(submitted_at)) AS ago, COUNT(*) AS total
         FROM tbl_concerns
         WHERE submitted_at >= (CURDATE() - INTERVAL 6 DAY)
         GROUP BY DATEDIFF(CURDATE(), DATE(submitted_at))"
    );
    $byAgo = [];
    foreach ($stmt->fetchAll() as $r) {
        $byAgo[(int) $r['ago']] = (int) $r['total'];
    }
    $labels = [];
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $labels[] = date('M j', strtotime("-$i days"));
        $data[] = $byAgo[$i] ?? 0;
    }
    $response['concerns_graph'] = ['labels' => $labels, 'data' => $data];

    // --- Common keywords across every barangay (top 8) ---
    $keywordCounts = [];
    $keywordRows = $pdo->query(
        "SELECT extracted_keywords FROM tbl_concerns
         WHERE extracted_keywords IS NOT NULL AND extracted_keywords != ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($keywordRows as $keywordString) {
        foreach (explode(',', $keywordString) as $keyword) {
            $keyword = trim($keyword);
            if ($keyword === '') continue;
            $keywordCounts[$keyword] = ($keywordCounts[$keyword] ?? 0) + 1;
        }
    }
    arsort($keywordCounts);
    $response['common_keywords'] = array_slice(array_keys($keywordCounts), 0, 8);

    // --- Most recent concerns (8, any barangay) ---
    $recent = $pdo->query(
        "SELECT c.concern_id, c.raw_text, c.submitted_at, b.barangay_name
         FROM tbl_concerns c
         JOIN tbl_barangay b ON b.barangay_id = c.barangay_id
         ORDER BY c.submitted_at DESC, c.concern_id DESC
         LIMIT 8"
    )->fetchAll();
    $response['recent_concerns'] = array_map(fn($r) => [
        'id'       => (int) $r['concern_id'],
        'barangay' => $r['barangay_name'],
        'date'     => date('n/j/Y', strtotime($r['submitted_at'])),
        'text'     => $r['raw_text'],
    ], $recent);

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading the overview.']);
    // error_log($e->getMessage());
}
