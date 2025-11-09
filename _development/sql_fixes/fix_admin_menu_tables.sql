-- ============================================
-- Fix Admin Menu: Add Missing Tables & Views
-- File: fix_admin_menu_tables.sql
-- Purpose: Add cabang, reseller tables and required views
-- ============================================

USE sinar_telkom_dashboard;

-- ============================================
-- Disable foreign key checks temporarily
-- ============================================
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Drop existing tables if they exist
-- ============================================
DROP TABLE IF EXISTS reseller;
DROP TABLE IF EXISTS cabang;

-- ============================================
-- Re-enable foreign key checks
-- ============================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Tabel: cabang
-- Menyimpan data kantor cabang
-- ============================================
CREATE TABLE IF NOT EXISTS cabang (
    cabang_id INT AUTO_INCREMENT PRIMARY KEY,
    kode_cabang VARCHAR(20) NOT NULL UNIQUE,
    nama_cabang VARCHAR(100) NOT NULL,
    alamat TEXT,
    kota VARCHAR(100),
    provinsi VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode_cabang (kode_cabang),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel: reseller
-- Menyimpan data mitra reseller
-- ============================================
CREATE TABLE IF NOT EXISTS reseller (
    reseller_id INT AUTO_INCREMENT PRIMARY KEY,
    kode_reseller VARCHAR(20) NOT NULL UNIQUE,
    nama_reseller VARCHAR(100) NOT NULL,
    nama_perusahaan VARCHAR(150),
    alamat TEXT,
    kota VARCHAR(100),
    provinsi VARCHAR(100),
    cabang_id INT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL,
    INDEX idx_kode_reseller (kode_reseller),
    INDEX idx_cabang_id (cabang_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Alter existing tables to add cabang_id
-- ============================================

-- Add cabang_id to users table if not exists
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS cabang_id INT NULL AFTER role;

-- Add cabang_id to produk table if not exists
ALTER TABLE produk 
ADD COLUMN IF NOT EXISTS cabang_id INT NULL AFTER harga_promo,
ADD FOREIGN KEY IF NOT EXISTS fk_produk_cabang (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL;

-- Add cabang_id to penjualan table if not exists
ALTER TABLE penjualan 
ADD COLUMN IF NOT EXISTS cabang_id INT NULL AFTER user_id,
ADD COLUMN IF NOT EXISTS reseller_id INT NULL AFTER cabang_id,
ADD FOREIGN KEY IF NOT EXISTS fk_penjualan_cabang (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL,
ADD FOREIGN KEY IF NOT EXISTS fk_penjualan_reseller (reseller_id) REFERENCES reseller(reseller_id) ON DELETE SET NULL;

-- Add cabang_id to inventory table if not exists
ALTER TABLE inventory 
ADD COLUMN IF NOT EXISTS cabang_id INT NULL AFTER produk_id,
ADD FOREIGN KEY IF NOT EXISTS fk_inventory_cabang (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL;

-- ============================================
-- Insert Sample Data: Cabang
-- ============================================
INSERT INTO cabang (kode_cabang, nama_cabang, alamat, kota, provinsi, status) VALUES
('JKT-01', 'Cabang Jakarta Pusat', 'Jl. Sudirman No. 123', 'Jakarta', 'DKI Jakarta', 'active'),
('JKT-02', 'Cabang Jakarta Selatan', 'Jl. TB Simatupang No. 456', 'Jakarta', 'DKI Jakarta', 'active'),
('BDG-01', 'Cabang Bandung', 'Jl. Asia Afrika No. 789', 'Bandung', 'Jawa Barat', 'active'),
('SBY-01', 'Cabang Surabaya', 'Jl. Basuki Rahmat No. 321', 'Surabaya', 'Jawa Timur', 'active'),
('MDN-01', 'Cabang Medan', 'Jl. Gatot Subroto No. 654', 'Medan', 'Sumatera Utara', 'active')
ON DUPLICATE KEY UPDATE nama_cabang=VALUES(nama_cabang);

-- ============================================
-- Insert Sample Data: Reseller
-- ============================================
INSERT INTO reseller (kode_reseller, nama_reseller, nama_perusahaan, alamat, kota, provinsi, cabang_id, status) VALUES
('RSL-001', 'Budi Reseller', 'CV Budi Jaya', 'Jl. Merdeka No. 100', 'Jakarta', 'DKI Jakarta', 1, 'active'),
('RSL-002', 'Siti Network', 'PT Siti Network', 'Jl. Pahlawan No. 200', 'Jakarta', 'DKI Jakarta', 2, 'active'),
('RSL-003', 'Ahmad Telkom', 'CV Ahmad Telkom', 'Jl. Veteran No. 300', 'Bandung', 'Jawa Barat', 3, 'active'),
('RSL-004', 'Dewi Connect', 'PT Dewi Connect', 'Jl. Pemuda No. 400', 'Surabaya', 'Jawa Timur', 4, 'active'),
('RSL-005', 'Joko Network', 'CV Joko Network', 'Jl. Diponegoro No. 500', 'Medan', 'Sumatera Utara', 5, 'active')
ON DUPLICATE KEY UPDATE nama_reseller=VALUES(nama_reseller);

-- ============================================
-- Update existing data with cabang_id
-- ============================================

-- Update users with cabang_id (non-administrator users)
UPDATE users SET cabang_id = 1 WHERE cabang_id IS NULL AND role != 'administrator' AND user_id % 5 = 1;
UPDATE users SET cabang_id = 2 WHERE cabang_id IS NULL AND role != 'administrator' AND user_id % 5 = 2;
UPDATE users SET cabang_id = 3 WHERE cabang_id IS NULL AND role != 'administrator' AND user_id % 5 = 3;
UPDATE users SET cabang_id = 4 WHERE cabang_id IS NULL AND role != 'administrator' AND user_id % 5 = 4;
UPDATE users SET cabang_id = 5 WHERE cabang_id IS NULL AND role != 'administrator' AND user_id % 5 = 0;

-- Update produk with random cabang_id
UPDATE produk SET cabang_id = 1 WHERE cabang_id IS NULL AND produk_id % 5 = 1;
UPDATE produk SET cabang_id = 2 WHERE cabang_id IS NULL AND produk_id % 5 = 2;
UPDATE produk SET cabang_id = 3 WHERE cabang_id IS NULL AND produk_id % 5 = 3;
UPDATE produk SET cabang_id = 4 WHERE cabang_id IS NULL AND produk_id % 5 = 4;
UPDATE produk SET cabang_id = 5 WHERE cabang_id IS NULL AND produk_id % 5 = 0;

-- Update penjualan with cabang_id and reseller_id
UPDATE penjualan SET cabang_id = 1, reseller_id = 1 WHERE cabang_id IS NULL AND penjualan_id % 5 = 1;
UPDATE penjualan SET cabang_id = 2, reseller_id = 2 WHERE cabang_id IS NULL AND penjualan_id % 5 = 2;
UPDATE penjualan SET cabang_id = 3, reseller_id = 3 WHERE cabang_id IS NULL AND penjualan_id % 5 = 3;
UPDATE penjualan SET cabang_id = 4, reseller_id = 4 WHERE cabang_id IS NULL AND penjualan_id % 5 = 4;
UPDATE penjualan SET cabang_id = 5, reseller_id = 5 WHERE cabang_id IS NULL AND penjualan_id % 5 = 0;

-- Update inventory with cabang_id
UPDATE inventory SET cabang_id = 1 WHERE cabang_id IS NULL AND inventory_id % 5 = 1;
UPDATE inventory SET cabang_id = 2 WHERE cabang_id IS NULL AND inventory_id % 5 = 2;
UPDATE inventory SET cabang_id = 3 WHERE cabang_id IS NULL AND inventory_id % 5 = 3;
UPDATE inventory SET cabang_id = 4 WHERE cabang_id IS NULL AND inventory_id % 5 = 4;
UPDATE inventory SET cabang_id = 5 WHERE cabang_id IS NULL AND inventory_id % 5 = 0;

-- ============================================
-- Create Views for Admin Panel
-- ============================================

-- View: Admin Dashboard Statistics
CREATE OR REPLACE VIEW view_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM cabang WHERE status = 'active') AS total_cabang,
    (SELECT COUNT(*) FROM reseller WHERE status = 'active') AS total_reseller,
    (SELECT COUNT(*) FROM users WHERE status = 'active') AS total_users,
    (SELECT COUNT(*) FROM produk WHERE status = 'active') AS total_produk,
    (SELECT COALESCE(SUM(total), 0) FROM penjualan WHERE status_pembayaran = 'paid') AS total_penjualan,
    (SELECT COALESCE(SUM(stok), 0) FROM produk WHERE status = 'active') AS total_stok;

-- View: Sales per Cabang
CREATE OR REPLACE VIEW view_sales_per_cabang AS
SELECT 
    c.cabang_id,
    c.kode_cabang,
    c.nama_cabang,
    c.kota,
    c.provinsi,
    COUNT(DISTINCT p.penjualan_id) AS total_transaksi,
    COALESCE(SUM(p.total), 0) AS total_penjualan,
    COUNT(DISTINCT r.reseller_id) AS jumlah_reseller,
    COUNT(DISTINCT pr.produk_id) AS jumlah_produk
FROM cabang c
LEFT JOIN penjualan p ON c.cabang_id = p.cabang_id AND p.status_pembayaran = 'paid'
LEFT JOIN reseller r ON c.cabang_id = r.cabang_id AND r.status = 'active'
LEFT JOIN produk pr ON c.cabang_id = pr.cabang_id AND pr.status = 'active'
WHERE c.status = 'active'
GROUP BY c.cabang_id, c.kode_cabang, c.nama_cabang, c.kota, c.provinsi
ORDER BY total_penjualan DESC;

-- View: Stock per Cabang
CREATE OR REPLACE VIEW view_stock_per_cabang AS
SELECT 
    c.cabang_id,
    c.kode_cabang,
    c.nama_cabang,
    p.produk_id,
    p.nama_produk,
    p.kategori,
    p.harga,
    COALESCE(SUM(i.stok_sesudah), 0) AS total_stok,
    COALESCE(SUM(i.stok_sesudah * p.harga), 0) AS nilai_stok
FROM cabang c
LEFT JOIN inventory i ON c.cabang_id = i.cabang_id
LEFT JOIN produk p ON i.produk_id = p.produk_id
WHERE c.status = 'active' AND p.status = 'active'
GROUP BY c.cabang_id, c.kode_cabang, c.nama_cabang, p.produk_id, p.nama_produk, p.kategori, p.harga
ORDER BY c.nama_cabang, total_stok DESC;

-- View: Reseller Performance
CREATE OR REPLACE VIEW view_reseller_performance AS
SELECT 
    r.reseller_id,
    r.kode_reseller,
    r.nama_reseller,
    r.nama_perusahaan,
    c.nama_cabang,
    COUNT(p.penjualan_id) AS total_transaksi,
    COALESCE(SUM(p.total), 0) AS total_penjualan,
    r.status
FROM reseller r
LEFT JOIN cabang c ON r.cabang_id = c.cabang_id
LEFT JOIN penjualan p ON r.reseller_id = p.reseller_id AND p.status_pembayaran = 'paid'
GROUP BY r.reseller_id, r.kode_reseller, r.nama_reseller, r.nama_perusahaan, c.nama_cabang, r.status
ORDER BY total_penjualan DESC;

-- ============================================
-- Success Message
-- ============================================
SELECT '✅ Database tables and views created successfully!' AS status;
SELECT 'Tables added: cabang, reseller' AS info1;
SELECT 'Views created: view_admin_dashboard, view_sales_per_cabang, view_stock_per_cabang, view_reseller_performance' AS info2;
SELECT 'Sample data inserted for cabang and reseller' AS info3;
SELECT 'Please refresh your admin panel to see the changes' AS info4;
