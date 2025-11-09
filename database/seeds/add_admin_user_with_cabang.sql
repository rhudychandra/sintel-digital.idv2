-- Add New Administrator User (WITH cabang_id column)
-- Username: rhudychandra
-- Password: Tsel2025
-- 
-- Use this file ONLY if you have already imported database_update_admin.sql
-- If you haven't, use add_admin_user.sql instead

USE sinar_telkom_dashboard;

-- Insert with cabang_id column (for updated database)
INSERT INTO users (username, password, full_name, email, phone, role, cabang_id, status) 
VALUES (
    'rhudychandra',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Rhudy Chandra',
    'rhudychandra@sinartelkom.com',
    '081234567890',
    'administrator',
    NULL,
    'active'
);

-- Verify the user was created
SELECT user_id, username, full_name, email, role, cabang_id, status 
FROM users 
WHERE username = 'rhudychandra';

-- Success messages
SELECT 'User rhudychandra created successfully!' as status;
SELECT 'Login with username: rhudychandra and password: Tsel2025' as info;
SELECT 'You can now access Admin Panel after login' as note;
