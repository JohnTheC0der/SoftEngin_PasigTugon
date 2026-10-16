-- Run ONCE on your existing pasig_tugon database (phpMyAdmin > Import or SQL tab).
-- Replaces the old, never-used tbl_backups with the version the Super Admin
-- backup feature needs, and adds the auto-backup settings table.
USE pasig_tugon;

DROP TABLE IF EXISTS tbl_backups;

-- ---------------------------------------------------
-- Table: tbl_backups
-- One row per database backup made by a super admin (manual),
-- the auto-backup schedule (auto), or the safety copy taken just
-- before a restore (pre_restore). The .sql files live in /backups.
-- ---------------------------------------------------
CREATE TABLE tbl_backups (
    backup_id         INT AUTO_INCREMENT PRIMARY KEY,
    backup_file_path  VARCHAR(255) NULL COMMENT 'file name inside the /backups folder',
    file_size         INT UNSIGNED NULL,
    backup_type       ENUM('manual', 'auto', 'pre_restore') NOT NULL DEFAULT 'manual',
    status            ENUM('success', 'failed') NOT NULL DEFAULT 'success',
    created_by        INT NULL COMMENT 'user_id of the super admin; NULL for scheduled runs',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_backups_created (created_at)
);

-- ---------------------------------------------------
-- Table: tbl_backup_settings
-- A single row (setting_id = 1) holding the auto-backup interval in days
-- ---------------------------------------------------
CREATE TABLE tbl_backup_settings (
    setting_id     TINYINT PRIMARY KEY DEFAULT 1,
    interval_days  TINYINT NOT NULL DEFAULT 1 COMMENT 'allowed: 1, 2, 3, 5, 7',
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO tbl_backup_settings (setting_id, interval_days) VALUES (1, 1);

