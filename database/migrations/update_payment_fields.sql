-- ============================================
-- Update ENUM values untuk metode_pembayaran dan status_pembayaran
-- Untuk mendukung: Transfer, Cash, Budget Komitmen, Finpay
-- Dan: Paid, Pending, TOP, Cancelled
-- ============================================

USE sinar_telkom_dashboard;

-- STEP 1: Backup data lama (opsional tapi direkomendasikan)
-- CREATE TABLE penjualan_backup AS SELECT * FROM penjualan;

-- STEP 2: Update kolom metode_pembayaran dengan semua nilai yang mungkin
ALTER TABLE penjualan 
MODIFY COLUMN metode_pembayaran VARCHAR(50) NOT NULL;

-- STEP 3: Update kolom status_pembayaran dengan semua nilai yang mungkin
ALTER TABLE penjualan 
MODIFY COLUMN status_pembayaran VARCHAR(50) DEFAULT 'Pending';

-- STEP 4: Update data lama ke format baru (jika ada)
UPDATE penjualan SET metode_pembayaran = 'Transfer' WHERE metode_pembayaran IN ('transfer', 'TRANSFER');
UPDATE penjualan SET metode_pembayaran = 'Cash' WHERE metode_pembayaran IN ('cash', 'CASH');
UPDATE penjualan SET status_pembayaran = 'Paid' WHERE status_pembayaran IN ('paid', 'PAID');
UPDATE penjualan SET status_pembayaran = 'Pending' WHERE status_pembayaran IN ('pending', 'PENDING');
UPDATE penjualan SET status_pembayaran = 'Cancelled' WHERE status_pembayaran IN ('cancelled', 'CANCELLED');

-- STEP 5: Verifikasi data
SELECT 
    'Metode Pembayaran' as field_name,
    metode_pembayaran as value,
    COUNT(*) as count
FROM penjualan 
GROUP BY metode_pembayaran

UNION ALL

SELECT 
    'Status Pembayaran' as field_name,
    status_pembayaran as value,
    COUNT(*) as count
FROM penjualan 
GROUP BY status_pembayaran;

SELECT '✅ Database updated successfully! Kolom sekarang menggunakan VARCHAR(50) untuk fleksibilitas maksimal.' AS message;

-- CATATAN PENTING:
-- Saya mengubah dari ENUM ke VARCHAR(50) karena:
-- 1. Lebih fleksibel - bisa menerima nilai apapun tanpa error
-- 2. Tidak perlu ALTER TABLE setiap kali ada nilai baru
-- 3. Menghindari masalah case-sensitive
-- 4. Lebih mudah untuk maintenance
-- 
-- Validasi nilai tetap dilakukan di level aplikasi (PHP)
