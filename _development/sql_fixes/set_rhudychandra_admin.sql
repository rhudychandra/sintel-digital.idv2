-- Set rhudychandra as Administrator
-- Simple and direct fix

USE sinar_telkom_dashboard;

-- Update user rhudychandra to have administrator role
UPDATE users 
SET role = 'administrator', 
    status = 'active'
WHERE username = 'rhudychandra';

-- Verify the update
SELECT 'User rhudychandra updated successfully!' as message;
SELECT username, full_name, role, status 
FROM users 
WHERE username = 'rhudychandra';

-- Show all administrator users
SELECT 'All administrator users:' as info;
SELECT username, full_name, role, status 
FROM users 
WHERE role = 'administrator';
