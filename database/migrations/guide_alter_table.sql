-- =====================================================================================
-- PANDUAN CARA MENAMBAH/MERUBAH TABLE PADA DATABASE SINAR TELEKOM DASHBOARD
-- =====================================================================================

USE sinar_telkom_dashboard;

-- ================================================================================
-- CARA 1: MENAMBAH KOLOM BARU KE TABLE YANG SUDAH ADA (ADD COLUMN)
-- ================================================================================

-- Contoh: Menambah kolom cabang_id ke tabel users
ALTER TABLE users ADD COLUMN cabang_id INT NULL AFTER role;

-- Contoh: Menambah kolom dengan index
ALTER TABLE users ADD COLUMN cabang_id INT NULL AFTER role,
                  ADD INDEX idx_cabang_id (cabang_id);

-- Contoh: Menambah kolom dengan foreign key
ALTER TABLE penjualan ADD COLUMN cabang_id INT NULL AFTER user_id,
                      ADD CONSTRAINT fk_penjualan_cabang
                      FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id);

-- ================================================================================
-- CARA 2: MENGUBAH TIPE DATA KOLOM (MODIFY COLUMN)
-- ================================================================================

-- Contoh: Mengubah role enum untuk menambah 'administrator'
ALTER TABLE users MODIFY COLUMN role ENUM('administrator', 'admin', 'manager', 'sales', 'staff', 'supervisor', 'finance') NOT NULL DEFAULT 'staff';

-- Contoh: Mengubah kolom menjadi NOT NULL
ALTER TABLE produk MODIFY COLUMN harga DECIMAL(15,2) NOT NULL;

-- ================================================================================
-- CARA 3: MENGHAPUS KOLOM (DROP COLUMN)
-- ================================================================================

-- Contoh: Menghapus kolom yang tidak diperlukan
ALTER TABLE users DROP COLUMN old_column_name;

-- ================================================================================
-- CARA 4: MENAMBAH INDEX BARU
-- ================================================================================

-- Contoh: Menambah index untuk performa query
ALTER TABLE penjualan ADD INDEX idx_tanggal_penjualan (tanggal_penjualan);
ALTER TABLE penjualan ADD INDEX idx_status_pembayaran (status_pembayaran);

-- ================================================================================
-- CARA 5: MEMBUAT TABLE BARU (CREATE TABLE)
-- ================================================================================

-- Contoh: Membuat tabel cabang
CREATE TABLE cabang (
    cabang_id INT AUTO_INCREMENT PRIMARY KEY,
    kode_cabang VARCHAR(10) NOT NULL UNIQUE,
    nama_cabang VARCHAR(100) NOT NULL,
    alamat TEXT,
    kota VARCHAR(50),
    provinsi VARCHAR(50),
    kode_pos VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contoh: Membuat tabel reseller dengan foreign key
CREATE TABLE reseller (
    reseller_id INT AUTO_INCREMENT PRIMARY KEY,
    kode_reseller VARCHAR(20) NOT NULL UNIQUE,
    nama_reseller VARCHAR(100) NOT NULL,
    nama_perusahaan VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    alamat TEXT,
    kota VARCHAR(50),
    provinsi VARCHAR(50),
    kode_pos VARCHAR(10),
    cabang_id INT,
    kategori ENUM('platinum','gold','silver','bronze') DEFAULT 'bronze',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cabang_id (cabang_id),
    INDEX idx_kategori (kategori),
    INDEX idx_status (status),
    FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id)
);

-- ================================================================================
-- CARA 6: MENGHAPUS TABLE (DROP TABLE)
-- ================================================================================

-- Hati-hati! Ini akan menghapus seluruh data
-- DROP TABLE nama_table_yang_tidak_diperlukan;

-- ================================================================================
-- CARA 7: MENAMBAH DATA AWAL (INSERT SEED DATA)
-- ================================================================================

-- Contoh: Menambah data cabang
INSERT INTO cabang (kode_cabang, nama_cabang, alamat, kota, provinsi, phone, email) VALUES
('CBG-001', 'Cabang Jakarta Pusat', 'Jl. Sudirman No. 1', 'Jakarta Pusat', 'DKI Jakarta', '021-1234567', 'jakarta@sinartelkom.com'),
('CBG-002', 'Cabang Bandung', 'Jl. Asia Afrika No. 100', 'Bandung', 'Jawa Barat', '022-7654321', 'bandung@sinartelkom.com');

-- Contoh: Menambah data reseller
INSERT INTO reseller (kode_reseller, nama_reseller, nama_perusahaan, email, phone, alamat, kota, provinsi, cabang_id, kategori) VALUES
('RSL-001', 'PT. Teknologi Maju', 'PT. Teknologi Maju', 'contact@teknomaju.com', '021-9876543', 'Jl. Thamrin No. 50', 'Jakarta Pusat', 'DKI Jakarta', 1, 'platinum'),
('RSL-002', 'CV. Digital Solution', 'CV. Digital Solution', 'info@digitalsol.com', '022-3456789', 'Jl. Braga No. 25', 'Bandung', 'Jawa Barat', 2, 'gold');

-- ================================================================================
-- CARA 8: MEMBUAT VIEW (CREATE VIEW)
-- ================================================================================

-- Contoh: View untuk dashboard admin
CREATE OR REPLACE VIEW view_admin_dashboard AS
SELECT
    (SELECT COUNT(*) FROM cabang WHERE status = 'active') as total_cabang,
    (SELECT COUNT(*) FROM reseller WHERE status = 'active') as total_reseller,
    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
    (SELECT COUNT(*) FROM produk) as total_produk,
    (SELECT COALESCE(SUM(total), 0) FROM penjualan WHERE status_pembayaran = 'paid') as total_penjualan,
    (SELECT COALESCE(SUM(stok), 0) FROM produk) as total_stok;

-- ================================================================================
-- CARA 9: MELIHAT STRUKTUR TABLE (DESCRIBE)
-- ================================================================================

-- Melihat struktur tabel
DESCRIBE users;
DESCRIBE produk;
DESCRIBE penjualan;
DESCRIBE cabang;
DESCRIBE reseller;

-- Melihat semua tabel
SHOW TABLES;

-- ================================================================================
-- CARA 10: BACKUP DAN RESTORE
-- ================================================================================

-- Backup database (jalankan di command line)
-- mysqldump -u root sinar_telkom_dashboard > backup.sql

-- Restore database (jalankan di command line)
-- mysql -u root sinar_telkom_dashboard < backup.sql

-- ================================================================================
-- TIPS PENTING
-- ================================================================================

/*
1. SELALU BACKUP DATABASE SEBELUM MELAKUKAN PERUBAHAN!
2. Gunakan transaksi untuk perubahan kritis:
   START TRANSACTION;
   -- lakukan perubahan
   COMMIT; -- atau ROLLBACK jika ada error

3. Test perubahan di development environment dulu
4. Update dokumentasi setelah perubahan
5. Pastikan foreign key constraints tidak terganggu
6. Gunakan naming convention yang konsisten
7. Tambahkan index untuk kolom yang sering di-query
8. Gunakan ENUM untuk data terbatas, VARCHAR untuk data fleksibel

8. Contoh penamaan:
   - Primary Key: nama_table_id (INT AUTO_INCREMENT)
   - Foreign Key: nama_table_id (INT)
   - Index: idx_nama_kolom
   - Foreign Key Constraint: fk_nama_table_kolom
*/

-- ================================================================================
-- CONTOH SCRIPT LENGKAP UNTUK MENAMBAH FITUR BARU
-- ================================================================================

-- Misal: Menambah fitur approval system
-- 1. Tambah kolom approval ke tabel penjualan
ALTER TABLE penjualan ADD COLUMN approval_status ENUM('pending','approved','rejected') DEFAULT 'pending' AFTER status_pengiriman,
                      ADD COLUMN approved_by INT NULL AFTER approval_status,
                      ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by,
                      ADD INDEX idx_approval_status (approval_status),
                      ADD CONSTRAINT fk_penjualan_approved_by FOREIGN KEY (approved_by) REFERENCES users(user_id);

-- 2. Update data existing
UPDATE penjualan SET approval_status = 'approved' WHERE status_pembayaran = 'paid';

-- 3. Buat view untuk approval
CREATE OR REPLACE VIEW view_pending_approvals AS
SELECT
    p.penjualan_id,
    p.no_invoice,
    p.tanggal_penjualan,
    p.total,
    u.full_name as sales_name,
    p.approval_status,
    p.created_at
FROM penjualan p
LEFT JOIN users u ON p.user_id = u.user_id
WHERE p.approval_status = 'pending'
ORDER BY p.created_at DESC;

SELECT '✅ Panduan alter table berhasil dibuat!' as status;
