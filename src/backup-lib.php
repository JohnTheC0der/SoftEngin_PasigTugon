<?php
// Backup engine for Pasig Tugon! — pure PHP, no mysqldump/mysql.exe needed.
//
// Backs up every app table (barangays, users, concerns) into one .sql file inside
// /backups (outside public/, and blocked from the web by an .htaccess file).
// The backup bookkeeping tables (tbl_backups, tbl_backup_settings) are deliberately
// NOT part of a backup, so restoring never erases the list of backups itself.

class PasigBackup
{
    const KEEP_BACKUPS    = 10;   // newest manual/auto backups kept; older files are deleted
    const HEADER          = '-- PASIG-TUGON-BACKUP v1';
    const FOOTER          = '-- END-OF-BACKUP';
    const MARKER          = "\n-- @@STMT@@\n";   // separates statements inside the file
    const EXCLUDED_TABLES = ['tbl_backups', 'tbl_backup_settings'];
    const INTERVAL_CHOICES = [1, 2, 3, 5, 7];     // days

    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── driver-specific SQL kept in small methods ──────────────────────────
    protected function listTables(): array
    {
        $rows = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        return array_values(array_diff(array_column($rows, 0), self::EXCLUDED_TABLES));
    }

    protected function createTableSql(string $table): string
    {
        $row = $this->pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        return $row[1];
    }

    protected function fkChecks(bool $on): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=' . ($on ? '1' : '0'));
    }

    /** Minutes since the newest successful manual/auto backup, or null if there is none. */
    protected function minutesSinceLastBackup(): ?int
    {
        $v = $this->pdo->query(
            "SELECT TIMESTAMPDIFF(MINUTE, MAX(created_at), NOW()) FROM tbl_backups
             WHERE status = 'success' AND backup_type IN ('manual','auto')"
        )->fetchColumn();
        return $v === null || $v === false ? null : (int) $v;
    }

    protected function recentFailureExists(): bool
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM tbl_backups
             WHERE status = 'failed' AND created_at > (NOW() - INTERVAL 30 MINUTE)"
        )->fetchColumn() > 0;
    }

    // ── helpers ────────────────────────────────────────────────────────────
    public function dir(): string
    {
        $dir = __DIR__ . '/../backups';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new RuntimeException('Could not create the backups folder.');
        }
        // Backups contain password hashes: make sure Apache never serves this folder
        $ht = $dir . '/.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }
        return $dir;
    }

    protected function quote($v): string
    {
        if ($v === null) return 'NULL';
        if (is_int($v) || is_float($v)) return (string) $v;
        if (is_bool($v)) return $v ? '1' : '0';
        return $this->pdo->quote((string) $v);
    }

    protected function record(?string $file, ?int $size, string $type, string $status, ?int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tbl_backups (backup_file_path, file_size, backup_type, status, created_by)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$file, $size, $type, $status, $userId]);
        return (int) $this->pdo->lastInsertId();
    }

    // ── settings ───────────────────────────────────────────────────────────
    public function intervalDays(): int
    {
        $v = $this->pdo->query("SELECT interval_days FROM tbl_backup_settings WHERE setting_id = 1")->fetchColumn();
        return in_array((int) $v, self::INTERVAL_CHOICES, true) ? (int) $v : 1;
    }

    public function saveInterval(int $days): void
    {
        if (!in_array($days, self::INTERVAL_CHOICES, true)) {
            throw new InvalidArgumentException('Invalid backup interval.');
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO tbl_backup_settings (setting_id, interval_days) VALUES (1, ?)
             ON DUPLICATE KEY UPDATE interval_days = VALUES(interval_days)"
        );
        $stmt->execute([$days]);
    }

    // ── create ─────────────────────────────────────────────────────────────
    /** @return array{id:int,file:string,size:int} */
    public function create(string $type = 'manual', ?int $userId = null): array
    {
        $dir  = $this->dir();
        $name = 'backup_' . date('Ymd_His') . '_' . $type . '.sql';
        if (file_exists("$dir/$name")) {
            $name = 'backup_' . date('Ymd_His') . '_' . $type . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.sql';
        }
        $path = "$dir/$name";
        $tmp  = $path . '.tmp';

        try {
            $fh = fopen($tmp, 'wb');
            if (!$fh) throw new RuntimeException('Could not write to the backups folder.');

            $w = function (string $sql) use ($fh) {
                fwrite($fh, rtrim($sql, "; \n\r\t") . ';' . self::MARKER);
            };

            fwrite($fh, self::HEADER . "\n-- Created: " . date('Y-m-d H:i:s') . "\n" . self::MARKER);

            foreach ($this->listTables() as $table) {
                $w("DROP TABLE IF EXISTS `$table`");
                $w($this->createTableSql($table));

                $batch = [];
                $cols  = null;
                $res   = $this->pdo->query("SELECT * FROM `$table`");
                while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                    if ($cols === null) {
                        $cols = '`' . implode('`,`', array_keys($row)) . '`';
                    }
                    $batch[] = '(' . implode(',', array_map([$this, 'quote'], array_values($row))) . ')';
                    if (count($batch) >= 200) {
                        $w("INSERT INTO `$table` ($cols) VALUES " . implode(',', $batch));
                        $batch = [];
                    }
                }
                if ($batch) {
                    $w("INSERT INTO `$table` ($cols) VALUES " . implode(',', $batch));
                }
            }

            fwrite($fh, self::FOOTER . "\n");
            fclose($fh);

            if (!rename($tmp, $path)) throw new RuntimeException('Could not finalize the backup file.');
            $size = (int) filesize($path);
            $id   = $this->record($name, $size, $type, 'success', $userId);
            $this->prune();

            return ['id' => $id, 'file' => $name, 'size' => $size];
        } catch (Throwable $e) {
            if (isset($fh) && is_resource($fh)) fclose($fh);
            @unlink($tmp);
            try { $this->record(null, null, $type, 'failed', $userId); } catch (Throwable $ignored) {}
            throw new RuntimeException('Backup failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // ── read ───────────────────────────────────────────────────────────────
    public function last(): ?array
    {
        $row = $this->pdo->query(
            "SELECT backup_id, backup_file_path, file_size, backup_type, created_at
             FROM tbl_backups
             WHERE status = 'success' AND backup_type IN ('manual','auto')
             ORDER BY created_at DESC, backup_id DESC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function recent(int $limit = 5): array
    {
        $limit = max(1, (int) $limit);
        return $this->pdo->query(
            "SELECT backup_id, backup_type, file_size, created_at
             FROM tbl_backups
             WHERE status = 'success' AND backup_type IN ('manual','auto')
             ORDER BY created_at DESC, backup_id DESC LIMIT $limit"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filePath(array $record): string
    {
        return $this->dir() . '/' . basename((string) $record['backup_file_path']);
    }

    // ── restore ────────────────────────────────────────────────────────────
    public function restoreFile(string $path): void
    {
        $content = is_file($path) ? file_get_contents($path) : false;
        if ($content === false
            || strpos($content, self::HEADER) !== 0
            || strpos($content, self::FOOTER) === false) {
            throw new RuntimeException('The backup file is missing, incomplete or not valid.');
        }

        $this->fkChecks(false);
        try {
            foreach (explode(self::MARKER, $content) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || strpos($stmt, '--') === 0) continue;
                $this->pdo->exec($stmt);
            }
        } finally {
            $this->fkChecks(true);
        }
    }

    // ── scheduling ─────────────────────────────────────────────────────────
    public function isDue(): bool
    {
        if ($this->recentFailureExists()) return false;   // don't hammer a broken setup
        $mins = $this->minutesSinceLastBackup();
        if ($mins === null) return true;
        // 60-minute tolerance so a daily scheduled run still counts as "2 days" etc.
        return $mins >= $this->intervalDays() * 1440 - 60;
    }

    /** Runs an automatic backup if the interval has passed. Returns the result or null. */
    public function runIfDue(): ?array
    {
        return $this->isDue() ? $this->create('auto', null) : null;
    }

    // ── retention ──────────────────────────────────────────────────────────
    protected function prune(): void
    {
        $this->pruneType("('manual','auto')", self::KEEP_BACKUPS);
        $this->pruneType("('pre_restore')", 1);
    }

    protected function pruneType(string $typesSql, int $keep): void
    {
        $keep = (int) $keep;
        $old = $this->pdo->query(
            "SELECT backup_id, backup_file_path FROM tbl_backups
             WHERE status = 'success' AND backup_type IN $typesSql
             ORDER BY created_at DESC, backup_id DESC LIMIT 100000 OFFSET $keep"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($old as $row) {
            if ($row['backup_file_path']) {
                @unlink($this->dir() . '/' . basename($row['backup_file_path']));
            }
            $this->pdo->prepare("DELETE FROM tbl_backups WHERE backup_id = ?")->execute([$row['backup_id']]);
        }
    }
}
