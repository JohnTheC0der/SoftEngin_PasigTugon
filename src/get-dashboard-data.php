<?php
// Returns everything the Admin Dashboard needs in one response:
// barangay name, quick stats, sector/priority pie chart data, the 7-day
// line graph, top 8 common keywords, and the 8 most recent concerns.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['barangay_id'])) {
    // super_admins have no barangay_id — this endpoint is for barangay admins only
    http_response_code(403);
    echo json_encode(['error' => 'No barangay associated with this account.']);
    exit;
}

$barangayId = $_SESSION['barangay_id'];

try {
    $response = [];

    // --- Barangay name ---
    $stmt = $pdo->prepare("SELECT barangay_name FROM tbl_barangay WHERE barangay_id = ?");
    $stmt->execute([$barangayId]);
    $response['barangay_name'] = $stmt->fetchColumn() ?: 'Unknown';

    // --- Quick stats ---
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_concerns WHERE barangay_id = ? AND status = 'ongoing'");
    $stmt->execute([$barangayId]);
    $response['total_concerns'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_concerns WHERE barangay_id = ? AND status = 'archived'");
    $stmt->execute([$barangayId]);
    $response['total_archived'] = (int) $stmt->fetchColumn();

    // --- Sector pie chart (ongoing only, per spec) ---
    $stmt = $pdo->prepare(
        "SELECT sector, COUNT(*) AS total FROM tbl_concerns
         WHERE barangay_id = ? AND status = 'ongoing'
         GROUP BY sector"
    );
    $stmt->execute([$barangayId]);
    $sectorRows = $stmt->fetchAll();
    $response['sector_chart'] = [
        'labels' => array_column($sectorRows, 'sector'),
        'data'   => array_map('intval', array_column($sectorRows, 'total'))
    ];

    // --- Priority pie chart (ongoing only, per spec) ---
    $stmt = $pdo->prepare(
        "SELECT priority_level, COUNT(*) AS total FROM tbl_concerns
         WHERE barangay_id = ? AND status = 'ongoing'
         GROUP BY priority_level"
    );
    $stmt->execute([$barangayId]);
    $priorityRows = $stmt->fetchAll();
    $response['priority_chart'] = [
        'labels' => array_column($priorityRows, 'priority_level'),
        'data'   => array_map('intval', array_column($priorityRows, 'total'))
    ];

    // --- 7-day concerns line graph (all statuses — counts concerns received, not just active ones) ---
    $stmt = $pdo->prepare(
        "SELECT DATE(submitted_at) AS day, COUNT(*) AS total
         FROM tbl_concerns
         WHERE barangay_id = ? AND submitted_at >= (CURDATE() - INTERVAL 6 DAY)
         GROUP BY DATE(submitted_at)"
    );
    $stmt->execute([$barangayId]);
    $countsByDay = [];
    foreach ($stmt->fetchAll() as $row) {
        $countsByDay[$row['day']] = (int) $row['total'];
    }

    // Fill in every one of the last 7 days, even the ones with zero concerns,
    // so the graph always has exactly 7 points.
    $graphLabels = [];
    $graphData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $graphLabels[] = date('M j', strtotime($date));
        $graphData[] = $countsByDay[$date] ?? 0;
    }
    $response['concerns_graph'] = ['labels' => $graphLabels, 'data' => $graphData];

    // --- Common keywords (top 8) ---
    // extracted_keywords is stored as a comma-separated string per concern.
    // We tally frequency in PHP since the dataset per barangay is small.
    $stmt = $pdo->prepare(
        "SELECT extracted_keywords FROM tbl_concerns
         WHERE barangay_id = ? AND extracted_keywords IS NOT NULL AND extracted_keywords != ''"
    );
    $stmt->execute([$barangayId]);
    $keywordCounts = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $keywordString) {
        foreach (explode(',', $keywordString) as $keyword) {
            $keyword = trim($keyword);
            if ($keyword === '') continue;
            $keywordCounts[$keyword] = ($keywordCounts[$keyword] ?? 0) + 1;
        }
    }
    arsort($keywordCounts);
    $response['common_keywords'] = array_slice(array_keys($keywordCounts), 0, 8);

    // --- Recent concerns (8 most recently submitted, any status) ---
    $stmt = $pdo->prepare(
        "SELECT concern_id, raw_text, submitted_at
         FROM tbl_concerns
         WHERE barangay_id = ?
         ORDER BY submitted_at DESC
         LIMIT 8"
    );
    $stmt->execute([$barangayId]);
    $response['recent_concerns'] = array_map(function ($row) {
        return [
            'id'   => (int) $row['concern_id'],
            'date' => date('n/j/Y', strtotime($row['submitted_at'])),
            'text' => $row['raw_text']
        ];
    }, $stmt->fetchAll());

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading dashboard data.']);
    // error_log($e->getMessage());
}
