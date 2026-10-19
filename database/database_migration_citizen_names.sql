-- =====================================================
-- Migration: citizens now have a full name (shown to staff and super admins).
-- Run ONCE on the existing pasig_tugon database (phpMyAdmin > Import).
-- Take a backup first (Super Admin > Settings > Database Backup).
-- =====================================================

USE pasig_tugon;

ALTER TABLE tbl_users
    ADD COLUMN full_name VARCHAR(100) NULL AFTER username;

-- Citizens who signed up before this change only have a username: use it as their
-- display name for now. They can keep signing in with that username.
UPDATE tbl_users SET full_name = username WHERE role = 'citizen' AND full_name IS NULL;

-- New citizens register with full name + email + password and sign in with their email.
