-- =====================================================
-- RESET ALL TRANSACTIONS - START FROM ZERO
-- =====================================================
-- This script will:
-- 1. Delete all sales transactions (penjualan & detail_penjualan)
-- 2. Delete all inventory records (stock history)
-- 3. Reset all product stock to 0
-- 4. Keep master data (produk, cabang, reseller, users)
-- =====================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Delete all detail penjualan
TRUNCATE TABLE detail_penjualan;

-- 2. Delete all penjualan
TRUNCATE TABLE penjualan;

-- 3. Delete all inventory records
TRUNCATE TABLE inventory;

-- 4. Reset all product stock to 0
UPDATE produk SET stok = 0 WHERE status = 'active';

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFICATION QUERIES (Run these to verify)
-- =====================================================
-- SELECT COUNT(*) as total_penjualan FROM penjualan;
-- SELECT COUNT(*) as total_detail FROM detail_penjualan;
-- SELECT COUNT(*) as total_inventory FROM inventory;
-- SELECT COUNT(*) as total_produk_with_stock FROM produk WHERE stok > 0;
-- =====================================================

SELECT 'All transactions have been reset successfully!' as status;
SELECT 'All product stock has been reset to 0' as stock_status;
SELECT 'Master data (produk, cabang, reseller, users) are preserved' as master_data_status;
