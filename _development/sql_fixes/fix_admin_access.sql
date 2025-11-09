-- Fix Admin Access for rhudychandra
-- This script will verify and fix the admin access issue

USE sinar_telkom_dashboard;

-- Step 1: Check current user status
SELECT 'Current user status:' as info;
SELECT user_id, username, full_name, role, status 
FROM users 
WHERE username = 'rhudychandra';

-- Step 2: Update user role to administrator (in case it's not set correctly)
UPDATE users 
SET role = 'administrator', 
    status = 'active'
WHERE username = 'rhudychandra';

-- Step 3: Verify the update
SELECT 'After update:' as info;
SELECT user_id, username, full_name, role, status 
FROM users 
WHERE username = 'rhudychandra';

-- Step 4: Check all administrator users
SELECT 'All administrator users:' as info;
SELECT user_id, username, full_name, role, status 
FROM users 
WHERE role = 'administrator';

-- Success message
SELECT 'Fix completed! Please logout and login again with rhudychandra / Tsel2025' as message;
SELECT 'The Administrator button should now appear on the dashboard' as note;
