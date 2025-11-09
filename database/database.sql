-- Database: sinar_telkom_dashboard
-- Dibuat untuk Sinar Telkom Dashboard System

-- Buat database
CREATE DATABASE IF NOT EXISTS sinar_telkom_dashboard;
USE sinar_telkom_dashboard;

-- ============================================
-- Tabel: users
-- Menyimpan data pengguna sistem
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'sales', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel: produk
-- Menyimpan data produk/layanan
-- ============================================
CREATE TABLE IF NOT EXISTS produk (
    produk_id INT AUTO_INCREMENT PRIMARY KEY,
    kode_produk VARCHAR(50) NOT NULL UNIQUE,
    nama_produk VARCHAR(200) NOT NULL,
    kategori ENUM('internet', 'tv_cable', 'phone', 'paket_bundling', 'enterprise') NOT NULL,
    deskripsi TEXT,
    harga DECIMAL(15,2) NOT NULL,
    harga_promo DECIMAL(15,2) NULL,
    stok INT DEFAULT 0,
    satuan VARCHAR(20) DEFAULT 'unit',
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode_produk (kode_produk),
    INDEX idx_kategori (kategori),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel: pelanggan
-- Menyimpan data pelanggan
-- ============================================
CREATE TABLE IF NOT EXISTS pelanggan (
    pelanggan_id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pelanggan VARCHAR(50) NOT NULL UNIQUE,
    nama_pelanggan VARCHAR(200) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    alamat TEXT,
    kota VARCHAR(100),
    provinsi VARCHAR(100),
    kode_pos VARCHAR(10),
    tipe_pelanggan ENUM('individual', 'corporate') DEFAULT 'individual',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode_pelanggan (kode_pelanggan),
    INDEX idx_phone (phone),
    INDEX idx_tipe_pelanggan (tipe_pelanggan),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel: penjualan
-- Menyimpan data transaksi penjualan
-- ============================================
CREATE TABLE IF NOT EXISTS penjualan (
    penjualan_id INT AUTO_INCREMENT PRIMARY KEY,
    no_invoice VARCHAR(50) NOT NULL UNIQUE,
    tanggal_penjualan DATE NOT NULL,
    pelanggan_id INT NOT NULL,
    user_id INT NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    diskon DECIMAL(15,2) DEFAULT 0,
    pajak DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,
    metode_pembayaran ENUM('cash', 'transfer', 'credit_card', 'debit_card', 'e-wallet') NOT NULL,
    status_pembayaran ENUM('pending', 'paid', 'cancelled', 'refunded') DEFAULT 'pending',
    status_pengiriman ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(pelanggan_id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_no_invoice (no_invoice),
    INDEX idx_tanggal_penjualan (tanggal_penjualan),
    INDEX idx_pelanggan_id (pelanggan_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status_pembayaran (status_pembayaran),
    INDEX idx_status_pengiriman (status_pengiriman)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel: detail_penjualan
-- Menyimpan detail item per transaksi penjualan
-- ============================================
CREATE TABLE IF NOT EXISTS detail_penjualan (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    penjualan_id INT NOT NULL,
    produk_id INT NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    jumlah INT NOT NULL,
    diskon DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (penjualan_id) REFERENCES penjualan(penjualan_id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(produk_id) ON DELETE RESTRICT,
    INDEX idx_penjualan_id (penjualan_id),
    INDEX idx_produk_id (produk_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel: inventory
-- Menyimpan data inventory/stok
-- ============================================
CREATE TABLE IF NOT EXISTS inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT NOT NULL,
    tanggal DATE NOT NULL,
    tipe_transaksi ENUM('masuk', 'keluar', 'adjustment', 'return') NOT NULL,
    jumlah INT NOT NULL,
    stok_sebelum INT NOT NULL,
    stok_sesudah INT NOT NULL,
    referensi VARCHAR(100),
    keterangan TEXT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produk_id) REFERENCES produk(produk_id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_produk_id (produk_id),
    INDEX idx_tanggal (tanggal),
    INDEX idx_tipe_transaksi (tipe_transaksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DATA SAMPLE
-- ============================================

-- Insert sample users
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@sinartelkom.com', '081234567890', 'admin', 'active'),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager Sales', 'manager@sinartelkom.com', '081234567891', 'manager', 'active'),
('sales1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales Representative 1', 'sales1@sinartelkom.com', '081234567892', 'sales', 'active'),
('sales2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales Representative 2', 'sales2@sinartelkom.com', '081234567893', 'sales', 'active'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Admin', 'staff@sinartelkom.com', '081234567894', 'staff', 'active');

-- Insert sample produk
INSERT INTO produk (kode_produk, nama_produk, kategori, deskripsi, harga, harga_promo, stok, status) VALUES
('INT-001', 'Internet 10 Mbps', 'internet', 'Paket internet rumah 10 Mbps unlimited', 250000.00, 225000.00, 100, 'active'),
('INT-002', 'Internet 20 Mbps', 'internet', 'Paket internet rumah 20 Mbps unlimited', 350000.00, 315000.00, 100, 'active'),
('INT-003', 'Internet 50 Mbps', 'internet', 'Paket internet rumah 50 Mbps unlimited', 500000.00, 450000.00, 100, 'active'),
('INT-004', 'Internet 100 Mbps', 'internet', 'Paket internet rumah 100 Mbps unlimited', 750000.00, 675000.00, 100, 'active'),
('TV-001', 'TV Cable Basic', 'tv_cable', 'Paket TV Cable 50 channel', 150000.00, NULL, 100, 'active'),
('TV-002', 'TV Cable Premium', 'tv_cable', 'Paket TV Cable 100 channel + HBO', 250000.00, 225000.00, 100, 'active'),
('PHN-001', 'Telepon Rumah', 'phone', 'Layanan telepon rumah unlimited lokal', 100000.00, NULL, 100, 'active'),
('BDL-001', 'Paket Triple Play 20 Mbps', 'paket_bundling', 'Internet 20 Mbps + TV Cable Premium + Telepon', 650000.00, 585000.00, 50, 'active'),
('BDL-002', 'Paket Triple Play 50 Mbps', 'paket_bundling', 'Internet 50 Mbps + TV Cable Premium + Telepon', 850000.00, 765000.00, 50, 'active'),
('ENT-001', 'Internet Corporate 100 Mbps', 'enterprise', 'Dedicated internet untuk perusahaan 100 Mbps', 5000000.00, NULL, 20, 'active');

-- Insert sample pelanggan
INSERT INTO pelanggan (kode_pelanggan, nama_pelanggan, email, phone, alamat, kota, provinsi, kode_pos, tipe_pelanggan, status) VALUES
('CUST-0001', 'Budi Santoso', 'budi.santoso@email.com', '081234567801', 'Jl. Merdeka No. 123', 'Jakarta', 'DKI Jakarta', '12345', 'individual', 'active'),
('CUST-0002', 'Siti Nurhaliza', 'siti.nur@email.com', '081234567802', 'Jl. Sudirman No. 456', 'Jakarta', 'DKI Jakarta', '12346', 'individual', 'active'),
('CUST-0003', 'Ahmad Wijaya', 'ahmad.w@email.com', '081234567803', 'Jl. Gatot Subroto No. 789', 'Bandung', 'Jawa Barat', '40123', 'individual', 'active'),
('CUST-0004', 'PT Maju Jaya', 'info@majujaya.com', '081234567804', 'Jl. HR Rasuna Said No. 100', 'Jakarta', 'DKI Jakarta', '12347', 'corporate', 'active'),
('CUST-0005', 'Dewi Lestari', 'dewi.lestari@email.com', '081234567805', 'Jl. Asia Afrika No. 50', 'Bandung', 'Jawa Barat', '40124', 'individual', 'active');

-- Insert sample penjualan
INSERT INTO penjualan (no_invoice, tanggal_penjualan, pelanggan_id, user_id, subtotal, diskon, pajak, total, metode_pembayaran, status_pembayaran, status_pengiriman) VALUES
('INV-2024-0001', '2024-01-15', 1, 3, 350000.00, 35000.00, 31500.00, 346500.00, 'transfer', 'paid', 'delivered'),
('INV-2024-0002', '2024-01-16', 2, 3, 650000.00, 65000.00, 58500.00, 643500.00, 'credit_card', 'paid', 'delivered'),
('INV-2024-0003', '2024-01-17', 3, 4, 500000.00, 50000.00, 45000.00, 495000.00, 'transfer', 'paid', 'processing'),
('INV-2024-0004', '2024-01-18', 4, 3, 5000000.00, 0.00, 500000.00, 5500000.00, 'transfer', 'paid', 'delivered'),
('INV-2024-0005', '2024-01-19', 5, 4, 850000.00, 85000.00, 76500.00, 841500.00, 'cash', 'paid', 'shipped');

-- Insert sample detail_penjualan
INSERT INTO detail_penjualan (penjualan_id, produk_id, nama_produk, harga_satuan, jumlah, diskon, subtotal) VALUES
(1, 2, 'Internet 20 Mbps', 350000.00, 1, 35000.00, 315000.00),
(2, 8, 'Paket Triple Play 20 Mbps', 650000.00, 1, 65000.00, 585000.00),
(3, 3, 'Internet 50 Mbps', 500000.00, 1, 50000.00, 450000.00),
(4, 10, 'Internet Corporate 100 Mbps', 5000000.00, 1, 0.00, 5000000.00),
(5, 9, 'Paket Triple Play 50 Mbps', 850000.00, 1, 85000.00, 765000.00);

-- Insert sample inventory
INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id) VALUES
(1, '2024-01-01', 'masuk', 100, 0, 100, 'PO-2024-001', 'Stok awal', 1),
(2, '2024-01-01', 'masuk', 100, 0, 100, 'PO-2024-001', 'Stok awal', 1),
(3, '2024-01-01', 'masuk', 100, 0, 100, 'PO-2024-001', 'Stok awal', 1),
(2, '2024-01-15', 'keluar', 1, 100, 99, 'INV-2024-0001', 'Penjualan ke Budi Santoso', 3),
(3, '2024-01-17', 'keluar', 1, 100, 99, 'INV-2024-0003', 'Penjualan ke Ahmad Wijaya', 4);

-- ============================================
-- VIEWS untuk Reporting
-- ============================================

-- View: Laporan Penjualan
CREATE OR REPLACE VIEW view_laporan_penjualan AS
SELECT 
    p.penjualan_id,
    p.no_invoice,
    p.tanggal_penjualan,
    pel.kode_pelanggan,
    pel.nama_pelanggan,
    u.full_name AS sales_person,
    p.subtotal,
    p.diskon,
    p.pajak,
    p.total,
    p.metode_pembayaran,
    p.status_pembayaran,
    p.status_pengiriman,
    p.created_at
FROM penjualan p
JOIN pelanggan pel ON p.pelanggan_id = pel.pelanggan_id
JOIN users u ON p.user_id = u.user_id
ORDER BY p.tanggal_penjualan DESC;

-- View: Stok Produk
CREATE OR REPLACE VIEW view_stok_produk AS
SELECT 
    pr.produk_id,
    pr.kode_produk,
    pr.nama_produk,
    pr.kategori,
    pr.harga,
    pr.harga_promo,
    pr.stok,
    pr.status,
    CASE 
        WHEN pr.stok <= 10 THEN 'Low Stock'
        WHEN pr.stok <= 50 THEN 'Medium Stock'
        ELSE 'High Stock'
    END AS status_stok
FROM produk pr
WHERE pr.status = 'active'
ORDER BY pr.stok ASC;

-- View: Top Selling Products
CREATE OR REPLACE VIEW view_top_selling_products AS
SELECT 
    pr.produk_id,
    pr.kode_produk,
    pr.nama_produk,
    pr.kategori,
    COUNT(dp.detail_id) AS jumlah_transaksi,
    SUM(dp.jumlah) AS total_terjual,
    SUM(dp.subtotal) AS total_revenue
FROM produk pr
JOIN detail_penjualan dp ON pr.produk_id = dp.produk_id
JOIN penjualan p ON dp.penjualan_id = p.penjualan_id
WHERE p.status_pembayaran = 'paid'
GROUP BY pr.produk_id, pr.kode_produk, pr.nama_produk, pr.kategori
ORDER BY total_revenue DESC;

-- View: Sales Performance
CREATE OR REPLACE VIEW view_sales_performance AS
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    u.role,
    COUNT(p.penjualan_id) AS total_transaksi,
    SUM(p.total) AS total_penjualan,
    AVG(p.total) AS rata_rata_transaksi,
    MAX(p.total) AS transaksi_terbesar
FROM users u
LEFT JOIN penjualan p ON u.user_id = p.user_id AND p.status_pembayaran = 'paid'
WHERE u.role IN ('sales', 'manager')
GROUP BY u.user_id, u.username, u.full_name, u.role
ORDER BY total_penjualan DESC;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- Procedure: Tambah Penjualan
DELIMITER //
CREATE PROCEDURE sp_tambah_penjualan(
    IN p_no_invoice VARCHAR(50),
    IN p_tanggal_penjualan DATE,
    IN p_pelanggan_id INT,
    IN p_user_id INT,
    IN p_subtotal DECIMAL(15,2),
    IN p_diskon DECIMAL(15,2),
    IN p_pajak DECIMAL(15,2),
    IN p_total DECIMAL(15,2),
    IN p_metode_pembayaran VARCHAR(20),
    OUT p_penjualan_id INT
)
BEGIN
    INSERT INTO penjualan (
        no_invoice, tanggal_penjualan, pelanggan_id, user_id,
        subtotal, diskon, pajak, total, metode_pembayaran
    ) VALUES (
        p_no_invoice, p_tanggal_penjualan, p_pelanggan_id, p_user_id,
        p_subtotal, p_diskon, p_pajak, p_total, p_metode_pembayaran
    );
    
    SET p_penjualan_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- Procedure: Update Stok
DELIMITER //
CREATE PROCEDURE sp_update_stok(
    IN p_produk_id INT,
    IN p_jumlah INT,
    IN p_tipe_transaksi VARCHAR(20),
    IN p_referensi VARCHAR(100),
    IN p_keterangan TEXT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_stok_sebelum INT;
    DECLARE v_stok_sesudah INT;
    
    -- Get current stock
    SELECT stok INTO v_stok_sebelum FROM produk WHERE produk_id = p_produk_id;
    
    -- Calculate new stock
    IF p_tipe_transaksi = 'masuk' THEN
        SET v_stok_sesudah = v_stok_sebelum + p_jumlah;
    ELSE
        SET v_stok_sesudah = v_stok_sebelum - p_jumlah;
    END IF;
    
    -- Update product stock
    UPDATE produk SET stok = v_stok_sesudah WHERE produk_id = p_produk_id;
    
    -- Insert inventory record
    INSERT INTO inventory (
        produk_id, tanggal, tipe_transaksi, jumlah,
        stok_sebelum, stok_sesudah, referensi, keterangan, user_id
    ) VALUES (
        p_produk_id, CURDATE(), p_tipe_transaksi, p_jumlah,
        v_stok_sebelum, v_stok_sesudah, p_referensi, p_keterangan, p_user_id
    );
END //
DELIMITER ;

-- ============================================
-- INDEXES untuk Performance
-- ============================================

-- Additional indexes for better query performance
CREATE INDEX idx_penjualan_tanggal_status ON penjualan(tanggal_penjualan, status_pembayaran);
CREATE INDEX idx_detail_penjualan_produk ON detail_penjualan(produk_id, penjualan_id);
CREATE INDEX idx_inventory_produk_tanggal ON inventory(produk_id, tanggal);

-- ============================================
-- NOTES
-- ============================================
-- Password default untuk semua user: 'password'
-- Untuk production, gunakan password yang lebih kuat dan hash dengan bcrypt
-- 
-- Cara import database:
-- 1. Buka phpMyAdmin
-- 2. Klik tab "Import"
-- 3. Pilih file database.sql ini
-- 4. Klik "Go"
--
-- Atau via command line:
-- mysql -u root -p < database.sql
