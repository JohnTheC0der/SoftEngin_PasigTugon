USE pasig_tugon;

-- 1. Insert Initial Barangay Records
INSERT INTO tbl_barangay (barangay_id, barangay_name, approval_status, approved_at) 
VALUES 
    (1, 'Barangay San Antonio', 'approved', NOW()),
    (2, 'Barangay Kapitolyo', 'approved', NOW()),
    (3, 'Barangay Ugong', 'pending', NULL)
ON DUPLICATE KEY UPDATE 
    approval_status = VALUES(approval_status),
    approved_at = VALUES(approved_at);

-- 2. Insert Dummy User Accounts
-- Password for all accounts is: Password123!
-- Note: Replace bcrypt hashes with your application's password hashing scheme if different.
INSERT INTO tbl_users (barangay_id, username, email, password_hash, role, is_active)
VALUES 
    -- Super Admin (Not tied to any barangay)
    (NULL, 'superadmin', 'superadmin@pasigtugon.gov.ph', '$2y$10$e.w2pU8C5rOa.KqS3J0g2.6iXJ/QvA2fN8E7A.dF5q5k7vN3/4j0y', 'super_admin', TRUE),
    
    -- Approved Barangay Admin (San Antonio)
    (1, 'admin_sanantonio', 'admin.sanantonio@pasigtugon.gov.ph', '$2y$10$e.w2pU8C5rOa.KqS3J0g2.6iXJ/QvA2fN8E7A.dF5q5k7vN3/4j0y', 'admin', TRUE),

    -- Approved Barangay Admin (Kapitolyo)
    (2, 'admin_kapitolyo', 'admin.kapitolyo@pasigtugon.gov.ph', '$2y$10$e.w2pU8C5rOa.KqS3J0g2.6iXJ/QvA2fN8E7A.dF5q5k7vN3/4j0y', 'admin', TRUE)
ON DUPLICATE KEY UPDATE is_active = TRUE;

-- 3. Execute Sean Dwyne's specific approval query
UPDATE tbl_barangay 
SET approval_status = 'approved', 
    approved_at = NOW() 
WHERE barangay_id = 1;