-- Add 'administrator' role to database (WITHOUT INFORMATION_SCHEMA)
-- Simple version that works without special privileges

USE sinar_telkom_dashboard;

-- Step 1: Modify the role column to include 'administrator'
-- This will add 'administrator' to the existing ENUM values
ALTER TABLE users 
MODIFY COLUMN role ENUM('administrator', 'admin', 'manager', 'sales', 'staff') 
NOT NULL DEFAULT 'staff';

-- Step 2: Update rhudychandra to administrator role
UPDATE users 
SET role = 'administrator', 
    status = 'active'
WHERE username = 'rhudychandra';

-- Step 3: Verify rhudychandra
SELECT 'User rhudychandra updated to administrator!' as message;
SELECT user_id, username, full_name, role, status 
FROM users 
WHERE username = 'rhudychandra';

-- Step 4: Show all users with their roles
SELECT 'All users in database:' as info;
SELECT user_id, username, full_name, role, status 
FROM users 
ORDER BY role, username;

-- Success messages
SELECT '✅ Setup Complete!' as status;
SELECT 'Role administrator has been added to database' as note1;
SELECT 'User rhudychandra is now an administrator' as note2;
SELECT 'Please logout and login again to see the Administrator button' as note3;
