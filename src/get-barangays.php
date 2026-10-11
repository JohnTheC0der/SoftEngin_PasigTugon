<?php
// Public list of barangays that are accepting concerns (approved admin exists).
// Returns slugs like "bagong-ilog" so the citizen form can disable the others.

header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    $rows = $pdo->query("SELECT barangay_name FROM tbl_barangay WHERE approval_status = 'approved'")
                ->fetchAll(PDO::FETCH_COLUMN);
    $slugs = array_map(fn($n) => str_replace(' ', '-', strtolower($n)), $rows);
    echo json_encode(['available' => array_values($slugs)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['available' => []]);
}
