<?php
// One-time helper for the roles migration (database_migration_roles_and_citizens.sql).
// Concerns that used to wait in "for review" have no sector/priority yet; this runs the
// model on each of them so every concern has both. CLI only.
//
// Usage:   C:\xampp\php\php.exe src\reclassify-legacy-concerns.php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/classifier.php';

$rows = $pdo->query(
    "SELECT concern_id, raw_text FROM tbl_concerns WHERE sector IS NULL OR priority_level IS NULL"
)->fetchAll();

if (!$rows) {
    echo "Nothing to do: every concern already has a sector and priority.\n";
    exit(0);
}

$update = $pdo->prepare(
    "UPDATE tbl_concerns SET sector = ?, priority_level = ?, confidence_score = ?, extracted_keywords = ?
     WHERE concern_id = ?"
);

$done = 0;
$failed = 0;
foreach ($rows as $row) {
    $result = classifyWithModel($row['raw_text']);
    if ($result === null) {
        $failed++;
        echo "Case #{$row['concern_id']}: the model could not classify it.\n";
        continue;
    }
    $update->execute([$result['sector'], $result['priority'], $result['confidence'], $result['keywords'], $row['concern_id']]);
    $done++;
}

echo "Classified $done concern(s). Remaining without a sector/priority: $failed.\n";
if ($failed === 0) {
    echo "You can now run the two ALTER TABLE lines at the bottom of the migration file.\n";
}
