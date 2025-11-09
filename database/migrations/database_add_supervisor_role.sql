-- ============================================
-- SQL Migration: Add Supervisor & Finance Roles
-- ============================================
-- Purpose: Add 'supervisor' and 'finance' roles to users table
-- Supervisor & Finance will have same access level as 'staff'
-- Date: 2024
-- ============================================

USE sinar_telkom_dashboard;

-- Step 1: Alter users table to add 'supervisor' and 'finance' to role ENUM
ALTER TABLE users 
MODIFY COLUMN role ENUM('administrator', 'admin', 'manager', 'sales', 'staff', 'supervisor', 'finance') DEFAULT 'staff';

-- Step 2: Verify the change (commented out to avoid permission issues)
-- SELECT COLUMN_TYPE 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'sinar_telkom_dashboard' 
--   AND TABLE_NAME = 'users' 
--   AND COLUMN_NAME = 'role';

-- Step 3 (Optional): Create sample users for testing
-- Password: 'password' (hashed with bcrypt)
INSERT INTO users (username, password, full_name, email, phone, role, status) 
VALUES 
    ('supervisor1', 
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
     'Supervisor Test', 
     'supervisor@sinartelkom.com', 
     '081234567899', 
     'supervisor', 
     'active'),
    ('finance1', 
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
     'Finance Test', 
     'finance@sinartelkom.com', 
     '081234567898', 
     'finance', 
     'active')
ON DUPLICATE KEY UPDATE username = username;

-- ============================================
-- Verification Queries
-- ============================================

-- Check if supervisor role exists in ENUM
SHOW COLUMNS FROM users LIKE 'role';

-- List all users with their roles
SELECT user_id, username, full_name, role, status 
FROM users 
ORDER BY 
    CASE role
        WHEN 'administrator' THEN 1
        WHEN 'admin' THEN 2
        WHEN 'manager' THEN 3
        WHEN 'supervisor' THEN 4
        WHEN 'staff' THEN 5
        WHEN 'sales' THEN 6
        ELSE 7
    END;

-- ============================================
-- Rollback (if needed)
-- ============================================
-- To rollback this change, run:
-- ALTER TABLE users 
-- MODIFY COLUMN role ENUM('administrator', 'admin', 'manager', 'sales', 'staff') DEFAULT 'staff';
-- DELETE FROM users WHERE role IN ('supervisor', 'finance');

-- ============================================
-- Notes
-- ============================================
-- 1. Supervisor & Finance roles have same access as Staff role
-- 2. No access to Administrator panel
-- 3. Can perform all inventory operations
-- 4. Default password for test users: 'password'
-- 5. Remember to update application code to handle supervisor and finance roles
