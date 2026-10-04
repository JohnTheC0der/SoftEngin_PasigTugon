<?php
// Returns the concerns list for this admin's barangay, filtered by status.
// GET param: filter = 'ongoing' (default) | 'archived' | 'all'

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

if (!in_array($filter, ['ongoing', 'archived', 'all'], true)) {
    $filter = 'ongoing';
}

try {
    if ($filter === 'all') {
        $stmt = $pdo->prepare(
            "SELECT concern_id, raw_text, status, submitted_at
             FROM tbl_concerns
             WHERE barangay_id = ?
             ORDER BY submitted_at DESC"
        );
        $stmt->execute([$barangayId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT concern_id, raw_text, status, submitted_at
             FROM tbl_concerns
             WHERE barangay_id = ? AND status = ?
             ORDER BY submitted_at DESC"
        );
        $stmt->execute([$barangayId, $filter]);
    }

    $concerns = array_map(function ($row) {
        return [
            'id'     => (int) $row['concern_id'],
            'date'   => date('n/j/Y', strtotime($row['submitted_at'])),
            'status' => $row['status'], // 'ongoing' or 'archived'
            'text'   => $row['raw_text']
        ];
    }, $stmt->fetchAll());

    echo json_encode(['concerns' => $concerns]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong loading concerns.']);
    // error_log($e->getMessage());
}
