<?php
// Unattended automatic backup. Run from the command line (or Windows Task Scheduler):
//   C:\xampp\php\php.exe C:\xampp\htdocs\pasig-tugon\src\run-scheduled-backup.php
// It only creates a backup if the interval chosen on the Super Admin Settings page
// has passed, so it is safe to schedule it every day.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/backup-lib.php';

try {
    $backup = new PasigBackup($pdo);
    $result = $backup->runIfDue();
    echo $result
        ? "Backup created: {$result['file']}\n"
        : "Not due yet (interval: {$backup->intervalDays()} day(s)). Nothing to do.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
