-- =====================================================================================
-- CONTOH PRAKTIS CARA MENAMBAH/MERUBAH TABLE
-- Script ini bisa langsung dijalankan tanpa error
-- =====================================================================================

USE sinar_telkom_dashboard;

-- ================================================================================
-- CONTOH 1: Menambah kolom baru ke tabel existing
-- ================================================================================

-- Menambah kolom 'last_activity' ke tabel users
ALTER TABLE users ADD COLUMN last_activity TIMESTAMP NULL AFTER last_login;

-- Menambah kolom 'komisi' ke tabel penjualan
ALTER TABLE penjualan ADD COLUMN komisi DECIMAL(10,2) DEFAULT 0.00 AFTER total;

-- ================================================================================
-- CONTOH 2: Membuat tabel baru untuk fitur baru
-- ================================================================================

-- Membuat tabel untuk approval system
CREATE TABLE approval_log (
    approval_id INT AUTO_INCREMENT PRIMARY KEY,
    penjualan_id INT NOT NULL,
    approved_by INT NOT NULL,
    action ENUM('approved','rejected') NOT NULL,
    notes TEXT,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_penjualan_id (penjualan_id),
    INDEX idx_approved_by (approved_by),
    FOREIGN KEY (penjualan_id) REFERENCES penjualan(penjualan_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

-- Membuat tabel untuk kategori produk
CREATE TABLE kategori_produk (
    kategori_id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ================================================================================
-- CONTOH 3: Menambah data awal (seed data)
-- ================================================================================

-- Menambah kategori produk default
INSERT INTO kategori_produk (nama_kategori, deskripsi) VALUES
('Internet', 'Layanan internet dan broadband'),
('TV Cable', 'Layanan televisi kabel'),
('Phone', 'Layanan telepon'),
('Paket Bundling', 'Paket kombinasi layanan'),
('Enterprise', 'Layanan untuk perusahaan');

-- ================================================================================
-- CONTOH 4: Membuat view baru
-- ================================================================================

-- View untuk monitoring approval
CREATE OR REPLACE VIEW view_approval_status AS
SELECT
    p.penjualan_id,
    p.no_invoice,
    p.total,
    u.full_name as sales_name,
    a.action as approval_status,
    a.approved_at,
    au.full_name as approved_by_name
FROM penjualan p
LEFT JOIN approval_log a ON p.penjualan_id = a.penjualan_id
LEFT JOIN users u ON p.user_id = u.user_id
LEFT JOIN users au ON a.approved_by = au.user_id
ORDER BY p.created_at DESC;

-- View untuk laporan komisi
CREATE OR REPLACE VIEW view_komisi_sales AS
SELECT
    u.user_id,
    u.username,
    u.full_name,
    COUNT(p.penjualan_id) as total_transaksi,
    COALESCE(SUM(p.total), 0) as total_penjualan,
    COALESCE(SUM(p.komisi), 0) as total_komisi,
    MONTH(p.tanggal_penjualan) as bulan,
    YEAR(p.tanggal_penjualan) as tahun
FROM users u
LEFT JOIN penjualan p ON u.user_id = p.user_id AND p.status_pembayaran = 'paid'
WHERE u.role IN ('sales', 'manager')
GROUP BY u.user_id, u.username, u.full_name, MONTH(p.tanggal_penjualan), YEAR(p.tanggal_penjualan);

-- ================================================================================
-- CONTOH 5: Menambah index untuk performa
-- ================================================================================

-- Index untuk query approval yang sering
ALTER TABLE approval_log ADD INDEX idx_action_status (action, approved_at);

-- Index untuk query komisi
ALTER TABLE penjualan ADD INDEX idx_user_tanggal (user_id, tanggal_penjualan);

-- ================================================================================
-- CONTOH 6: Update data existing
-- ================================================================================

-- Set komisi default berdasarkan role
UPDATE penjualan p
JOIN users u ON p.user_id = u.user_id
SET p.komisi = CASE
    WHEN u.role = 'sales' THEN p.total * 0.05  -- 5% komisi untuk sales
    WHEN u.role = 'manager' THEN p.total * 0.08 -- 8% komisi untuk manager
    ELSE 0
END
WHERE p.status_pembayaran = 'paid';

-- Update last_activity untuk users aktif
UPDATE users SET last_activity = NOW() WHERE status = 'active';

-- ================================================================================
-- CONTOH 7: Menambah constraint dan validasi
-- ================================================================================

-- Menambah check constraint untuk komisi tidak boleh negatif
ALTER TABLE penjualan ADD CONSTRAINT chk_komisi_positive CHECK (komisi >= 0);

-- Menambah unique constraint untuk kombinasi kode
ALTER TABLE cabang ADD CONSTRAINT uk_kode_cabang UNIQUE (kode_cabang);

-- ================================================================================
-- CONTOH 8: Script untuk rollback (jika perlu)
-- ================================================================================

-- Uncomment baris di bawah ini jika perlu rollback perubahan
/*
-- Hapus kolom yang ditambahkan
ALTER TABLE users DROP COLUMN last_activity;
ALTER TABLE penjualan DROP COLUMN komisi;

-- Hapus tabel yang dibuat
DROP TABLE IF EXISTS approval_log;
DROP TABLE IF EXISTS kategori_produk;

-- Hapus view yang dibuat
DROP VIEW IF EXISTS view_approval_status;
DROP VIEW IF EXISTS view_komisi_sales;
*/

-- ================================================================================
-- VERIFIKASI PERUBAHAN
-- ================================================================================

-- Cek struktur tabel yang diubah
DESCRIBE users;
DESCRIBE penjualan;

-- Cek tabel baru
SHOW TABLES LIKE 'approval_log';
SHOW TABLES LIKE 'kategori_produk';

-- Cek view baru
SELECT 'View approval_status:' as info;
SELECT COUNT(*) as total_records FROM view_approval_status;

SELECT 'View komisi_sales:' as info;
SELECT COUNT(*) as total_records FROM view_komisi_sales;

-- Cek data kategori
SELECT 'Data kategori_produk:' as info;
SELECT * FROM kategori_produk;

SELECT '✅ Semua perubahan berhasil diterapkan!' as status;
