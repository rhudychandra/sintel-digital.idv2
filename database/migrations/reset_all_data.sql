-- =====================================================
-- RESET ALL TRANSACTION DATA - KEEP MASTER DATA
-- =====================================================
-- PENTING: Script ini akan menghapus SEMUA data transaksi!
-- 
-- Yang DIHAPUS:
--   - Semua penjualan
--   - Semua detail penjualan
--   - Semua inventory records
--   - Semua pelanggan
--   - Semua setoran harian TAP
--   - Semua evidence setoran
--   - Reset stock produk ke 0
--
-- Yang DIPERTAHANKAN:
--   - Master produk
--   - Master cabang
--   - Master reseller
--   - User accounts
--   - Kategori produk
--
-- CARA MENGGUNAKAN:
--   1. BACKUP DATABASE DULU!
--   2. Login ke phpMyAdmin
--   3. Pilih database Anda
--   4. Klik tab "SQL"
--   5. Copy-paste script ini
--   6. Klik "Go" untuk eksekusi
--   7. Verifikasi hasilnya
-- =====================================================

-- Disable foreign key checks untuk menghindari constraint errors
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- STEP 1: Delete all detail_penjualan
-- =====================================================
DELETE FROM detail_penjualan;

-- =====================================================
-- STEP 2: Delete all penjualan
-- =====================================================
DELETE FROM penjualan;

-- =====================================================
-- STEP 3: Delete all inventory records
-- =====================================================
DELETE FROM inventory;

-- =====================================================
-- STEP 4: Reset all product stock to 0
-- =====================================================
UPDATE produk SET stok = 0;

-- =====================================================
-- STEP 5: Delete all pelanggan (customers)
-- =====================================================
DELETE FROM pelanggan;

-- =====================================================
-- STEP 6: Delete all setoran harian TAP records
-- =====================================================
DELETE FROM setoran_harian;

-- =====================================================
-- STEP 7: Delete all setoran evidence records
-- =====================================================
DELETE FROM setoran_evidence;

-- =====================================================
-- STEP 8: Reset AUTO_INCREMENT counters (optional)
-- =====================================================
-- Uncomment jika ingin reset ID counter ke 1:
-- ALTER TABLE detail_penjualan AUTO_INCREMENT = 1;
-- ALTER TABLE penjualan AUTO_INCREMENT = 1;
-- ALTER TABLE inventory AUTO_INCREMENT = 1;
-- ALTER TABLE pelanggan AUTO_INCREMENT = 1;
-- ALTER TABLE setoran_harian AUTO_INCREMENT = 1;
-- ALTER TABLE setoran_evidence AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Check hasil reset (harusnya semua 0):

SELECT 'PENJUALAN' as Tabel, COUNT(*) as Total FROM penjualan
UNION ALL
SELECT 'DETAIL_PENJUALAN', COUNT(*) FROM detail_penjualan
UNION ALL
SELECT 'INVENTORY', COUNT(*) FROM inventory
UNION ALL
SELECT 'PELANGGAN', COUNT(*) FROM pelanggan
UNION ALL
SELECT 'SETORAN_HARIAN', COUNT(*) FROM setoran_harian
UNION ALL
SELECT 'SETORAN_EVIDENCE', COUNT(*) FROM setoran_evidence
UNION ALL
SELECT 'PRODUK_STOK_>_0', COUNT(*) FROM produk WHERE stok > 0;

-- =====================================================
-- Jika semua menunjukkan 0, maka reset BERHASIL! ✅
-- =====================================================
