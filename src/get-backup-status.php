<?php
// Backup info for the Super Admin settings page.
// Also runs an automatic backup first if the chosen interval has passed.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'backup-lib.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized.']);
    exit;
}

try {
    $backup = new PasigBackup($pdo);

    $autoRan = false;
    try {
        $autoRan = $backup->runIfDue() !== null;
    } catch (Throwable $e) {
        // A failed automatic backup is logged in tbl_backups; don't break the page over it
    }

    $last = $backup->last();
    echo json_encode([
        'interval_days' => $backup->intervalDays(),
        'last'          => $last ? [
            'created_at' => $last['created_at'],
            'type'       => $last['backup_type'],
            'size'       => (int) $last['file_size'],
            'file_ok'    => is_file($backup->filePath($last)),
        ] : null,
        'recent'        => array_map(function ($r) {
            return ['created_at' => $r['created_at'], 'type' => $r['backup_type'], 'size' => (int) $r['file_size']];
        }, $backup->recent(5)),
        'auto_ran'      => $autoRan,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load backup information. Did you run the database migration?']);
    // error_log($e->getMessage());
}
