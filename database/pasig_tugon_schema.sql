-- =====================================================
-- PASIG TUGON! — Community Priority Inference System
-- Bayesian Text Classification for Urban Barangay Governance
-- Database engine: MySQL 8.0+
-- =====================================================

CREATE DATABASE IF NOT EXISTS pasig_tugon
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pasig_tugon;

-- ---------------------------------------------------
-- Table: tbl_barangay
-- Barangay master records and admin approval status
-- ---------------------------------------------------
CREATE TABLE tbl_barangay (
    barangay_id     INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name   VARCHAR(100) NOT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at     TIMESTAMP NULL
);

-- ---------------------------------------------------
-- Table: tbl_users
-- Admin credentials, roles, and authentication state
-- ---------------------------------------------------
CREATE TABLE tbl_users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    barangay_id    INT NULL COMMENT 'NULL for super_admin accounts, which are not tied to a barangay',
    username       VARCHAR(50) NOT NULL UNIQUE,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('admin', 'super_admin') NOT NULL DEFAULT 'admin',
    is_active      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at  TIMESTAMP NULL,
    FOREIGN KEY (barangay_id) REFERENCES tbl_barangay(barangay_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- ---------------------------------------------------
-- Table: tbl_concerns
-- Raw complaint text, extracted keywords, sector, and priority (set by ml_engine/)
-- ---------------------------------------------------
CREATE TABLE tbl_concerns (
    concern_id         INT AUTO_INCREMENT PRIMARY KEY,
    barangay_id        INT NOT NULL,
    raw_text           TEXT NOT NULL,
    extracted_keywords VARCHAR(500),
    sector             ENUM('Health', 'Infrastructure', 'Crime', 'Environment') NULL COMMENT 'NULL while status = for_review (model confidence too low)',
    confidence_score   DECIMAL(5,4),
    priority_level     ENUM('High', 'Medium', 'Low') NULL COMMENT 'NULL while status = for_review',
    status             ENUM('ongoing', 'for_review', 'archived') NOT NULL DEFAULT 'ongoing',
    submitted_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at        TIMESTAMP NULL,
    FOREIGN KEY (barangay_id) REFERENCES tbl_barangay(barangay_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_barangay_status (barangay_id, status),
    INDEX idx_priority (priority_level),
    INDEX idx_sector (sector),
    INDEX idx_submitted_at (submitted_at)
);

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

-- Note: urgency keywords live in ml_engine/priority.py (URGENCY_WEIGHTS), not in the database.
