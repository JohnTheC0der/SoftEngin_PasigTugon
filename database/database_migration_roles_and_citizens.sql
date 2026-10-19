-- =====================================================
-- Migration: 3 roles (citizen / staff / super_admin), citizen-linked concerns,
-- and removal of the "for_review" status.
-- Run ONCE on the existing pasig_tugon database (phpMyAdmin > Import).
-- Take a backup first (Super Admin > Settings > Database Backup).
-- =====================================================

USE pasig_tugon;

-- 1) Roles: add 'citizen' and rename 'admin' -> 'staff'
ALTER TABLE tbl_users
    MODIFY role ENUM('citizen', 'admin', 'staff', 'super_admin') NOT NULL DEFAULT 'citizen';
UPDATE tbl_users SET role = 'staff' WHERE role = 'admin';
ALTER TABLE tbl_users
    MODIFY role ENUM('citizen', 'staff', 'super_admin') NOT NULL DEFAULT 'citizen';

-- 2) Link each concern to the citizen who sent it.
--    Existing concerns were anonymous, so their user_id stays NULL
--    (staff still see them; no citizen dashboard will list them).
ALTER TABLE tbl_concerns
    ADD COLUMN user_id INT NULL AFTER barangay_id,
    ADD INDEX idx_user (user_id),
    ADD CONSTRAINT fk_concerns_user FOREIGN KEY (user_id) REFERENCES tbl_users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- 3) Remove 'for_review': those concerns simply become ongoing.
--    (Rows that never got a sector/priority are filled in by step A below.)
UPDATE tbl_concerns SET status = 'ongoing' WHERE status = 'for_review';
ALTER TABLE tbl_concerns
    MODIFY status ENUM('ongoing', 'archived') NOT NULL DEFAULT 'ongoing';

-- =====================================================
-- OPTIONAL, only if you had concerns waiting for review (sector/priority = NULL):
--   A) From the project folder run:   C:\xampp\php\php.exe src\reclassify-legacy-concerns.php
--      It runs the model on every concern that has no sector/priority yet.
--   B) When it reports 0 remaining, run these two lines to make the columns mandatory:
--
-- ALTER TABLE tbl_concerns MODIFY sector ENUM('Health','Infrastructure','Crime','Environment') NOT NULL;
-- ALTER TABLE tbl_concerns MODIFY priority_level ENUM('High','Medium','Low') NOT NULL;
-- =====================================================
