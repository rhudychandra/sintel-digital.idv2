-- ============================================
-- CLEAR ALL SALES DATA
-- ============================================
-- WARNING: This will DELETE ALL sales transactions!
-- Use this ONLY for testing/development purposes
-- BACKUP your database before running this!
-- ============================================

USE sinar_telkom_dashboard;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Clear detail_penjualan FIRST (child table)
DELETE FROM detail_penjualan;

-- Clear penjualan SECOND (parent table)
DELETE FROM penjualan;

-- Clear inventory records (REQUIRED to delete products)
DELETE FROM inventory;

-- Reset auto increment
ALTER TABLE penjualan AUTO_INCREMENT = 1;
ALTER TABLE detail_penjualan AUTO_INCREMENT = 1;
ALTER TABLE inventory AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify deletion
SELECT 'Sales data cleared successfully!' as status;
SELECT COUNT(*) as remaining_penjualan FROM penjualan;
SELECT COUNT(*) as remaining_detail FROM detail_penjualan;

-- ============================================
-- NOTES:
-- ============================================
-- After running this script:
-- 1. All sales transactions will be deleted
-- 2. All sales details will be deleted
-- 3. All inventory records will be deleted
-- 4. Auto increment counters reset to 1
-- 5. You can now delete products freely
-- 6. You can now delete categories freely
-- 
-- To restore data, you need to:
-- - Import from backup
-- - Or re-enter transactions manually
-- 
-- Why DELETE instead of TRUNCATE?
-- - TRUNCATE doesn't work with foreign keys
-- - DELETE removes all rows safely
-- - We reset AUTO_INCREMENT manually after
-- ============================================
