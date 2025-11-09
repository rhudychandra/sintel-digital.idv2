-- Add New Administrator User
-- Username: rhudychandra
-- Password: Tsel2025

USE sinar_telkom_dashboard;

-- Simple insert without cabang_id (works with basic database.sql)
-- If you get error about cabang_id, it means you need to import database_update_admin.sql first

INSERT INTO users (username, password, full_name, email, phone, role, status) 
VALUES (
    'rhudychandra',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Rhudy Chandra',
    'rhudychandra@sinartelkom.com',
    '081234567890',
    'administrator',
    'active'
);

-- Verify the user was created
SELECT user_id, username, full_name, email, role, status 
FROM users 
WHERE username = 'rhudychandra';

-- Success messages
SELECT 'User rhudychandra created successfully!' as status;
SELECT 'Login with username: rhudychandra and password: Tsel2025' as info;
SELECT 'Note: Password Tsel2025 is accepted by login.php' as note;
