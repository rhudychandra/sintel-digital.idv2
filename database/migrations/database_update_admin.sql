-- Update Database untuk Administrator Feature
-- Jalankan setelah database.sql sudah diimport

USE sinar_telkom_dashboard;

-- 1. Tambah tabel Cabang (Branches)
CREATE TABLE IF NOT EXISTS cabang (
    cabang_id INT PRIMARY KEY AUTO_INCREMENT,
    kode_cabang VARCHAR(20) UNIQUE NOT NULL,
    nama_cabang VARCHAR(100) NOT NULL,
    alamat TEXT,
    kota VARCHAR(50),
    provinsi VARCHAR(50),
    telepon VARCHAR(20),
    email VARCHAR(100),
    manager_name VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tambah tabel Reseller
CREATE TABLE IF NOT EXISTS reseller (
    reseller_id INT PRIMARY KEY AUTO_INCREMENT,
    kode_reseller VARCHAR(20) UNIQUE NOT NULL,
    nama_reseller VARCHAR(100) NOT NULL,
    nama_perusahaan VARCHAR(100),
    alamat TEXT,
    kota VARCHAR(50),
    provinsi VARCHAR(50),
    telepon VARCHAR(20),
    email VARCHAR(100),
    contact_person VARCHAR(100),
    cabang_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    total_penjualan DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Update tabel users - tambah kolom cabang_id
ALTER TABLE users ADD COLUMN IF NOT EXISTS cabang_id INT AFTER role;
ALTER TABLE users ADD FOREIGN KEY IF NOT EXISTS (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL;

-- 4. Update tabel produk - tambah kolom cabang_id
ALTER TABLE produk ADD COLUMN IF NOT EXISTS cabang_id INT AFTER kategori;
ALTER TABLE produk ADD FOREIGN KEY IF NOT EXISTS (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL;

-- 5. Update tabel inventory - tambah kolom cabang_id
ALTER TABLE inventory ADD COLUMN IF NOT EXISTS cabang_id INT AFTER produk_id;
ALTER TABLE inventory ADD FOREIGN KEY IF NOT EXISTS (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL;

-- 6. Update tabel penjualan - tambah kolom cabang_id dan reseller_id
ALTER TABLE penjualan ADD COLUMN IF NOT EXISTS cabang_id INT AFTER user_id;
ALTER TABLE penjualan ADD COLUMN IF NOT EXISTS reseller_id INT AFTER cabang_id;
ALTER TABLE penjualan ADD FOREIGN KEY IF NOT EXISTS (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL;
ALTER TABLE penjualan ADD FOREIGN KEY IF NOT EXISTS (reseller_id) REFERENCES reseller(reseller_id) ON DELETE SET NULL;

-- 7. Insert sample data untuk Cabang
INSERT INTO cabang (kode_cabang, nama_cabang, alamat, kota, provinsi, telepon, email, manager_name, status) VALUES
('JKT001', 'Cabang Jakarta Pusat', 'Jl. Sudirman No. 123', 'Jakarta', 'DKI Jakarta', '021-12345678', 'jakarta@sinartelkom.com', 'Budi Santoso', 'active'),
('BDG001', 'Cabang Bandung', 'Jl. Asia Afrika No. 45', 'Bandung', 'Jawa Barat', '022-87654321', 'bandung@sinartelkom.com', 'Siti Nurhaliza', 'active'),
('SBY001', 'Cabang Surabaya', 'Jl. Tunjungan No. 67', 'Surabaya', 'Jawa Timur', '031-23456789', 'surabaya@sinartelkom.com', 'Ahmad Yani', 'active'),
('MDN001', 'Cabang Medan', 'Jl. Gatot Subroto No. 89', 'Medan', 'Sumatera Utara', '061-34567890', 'medan@sinartelkom.com', 'Dewi Lestari', 'active'),
('DPS001', 'Cabang Denpasar', 'Jl. Sunset Road No. 12', 'Denpasar', 'Bali', '0361-45678901', 'denpasar@sinartelkom.com', 'Made Wirawan', 'active');

-- 8. Insert sample data untuk Reseller
INSERT INTO reseller (kode_reseller, nama_reseller, nama_perusahaan, alamat, kota, provinsi, telepon, email, contact_person, cabang_id, status, total_penjualan) VALUES
('RSL001', 'Toko Elektronik Jaya', 'PT Elektronik Jaya Abadi', 'Jl. Mangga Dua No. 10', 'Jakarta', 'DKI Jakarta', '021-11111111', 'jaya@email.com', 'John Doe', 1, 'active', 50000000),
('RSL002', 'Mega Telkom Store', 'CV Mega Telkom', 'Jl. Dago No. 20', 'Bandung', 'Jawa Barat', '022-22222222', 'mega@email.com', 'Jane Smith', 2, 'active', 35000000),
('RSL003', 'Surabaya Tech', 'PT Surabaya Technology', 'Jl. Pemuda No. 30', 'Surabaya', 'Jawa Timur', '031-33333333', 'tech@email.com', 'Robert Brown', 3, 'active', 42000000),
('RSL004', 'Medan Digital', 'CV Medan Digital Solution', 'Jl. Iskandar Muda No. 40', 'Medan', 'Sumatera Utara', '061-44444444', 'digital@email.com', 'Sarah Wilson', 4, 'active', 28000000),
('RSL005', 'Bali Gadget Center', 'PT Bali Gadget', 'Jl. Kuta No. 50', 'Denpasar', 'Bali', '0361-55555555', 'gadget@email.com', 'Michael Lee', 5, 'active', 31000000);

-- 9. Tambah user Administrator
INSERT INTO users (username, password, full_name, email, phone, role, cabang_id, status) VALUES
('administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'admin@sinartelkom.com', '081234567890', 'administrator', NULL, 'active');

-- 10. Update existing users dengan cabang_id
UPDATE users SET cabang_id = 1 WHERE username = 'admin';
UPDATE users SET cabang_id = 2 WHERE username = 'manager1';
UPDATE users SET cabang_id = 1 WHERE username = 'sales1';
UPDATE users SET cabang_id = 3 WHERE username = 'sales2';
UPDATE users SET cabang_id = 1 WHERE username = 'staff1';

-- 11. Update produk dengan cabang_id
UPDATE produk SET cabang_id = 1 WHERE produk_id IN (1, 2);
UPDATE produk SET cabang_id = 2 WHERE produk_id IN (3, 4);
UPDATE produk SET cabang_id = 3 WHERE produk_id = 5;

-- 12. Update inventory dengan cabang_id
UPDATE inventory SET cabang_id = 1 WHERE produk_id IN (1, 2);
UPDATE inventory SET cabang_id = 2 WHERE produk_id IN (3, 4);
UPDATE inventory SET cabang_id = 3 WHERE produk_id = 5;

-- 13. Update penjualan dengan cabang_id dan reseller_id
UPDATE penjualan SET cabang_id = 1, reseller_id = 1 WHERE penjualan_id = 1;
UPDATE penjualan SET cabang_id = 2, reseller_id = 2 WHERE penjualan_id = 2;
UPDATE penjualan SET cabang_id = 3, reseller_id = 3 WHERE penjualan_id = 3;

-- 14. Create View untuk Admin Dashboard
CREATE OR REPLACE VIEW view_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM cabang WHERE status = 'active') as total_cabang,
    (SELECT COUNT(*) FROM reseller WHERE status = 'active') as total_reseller,
    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
    (SELECT COUNT(*) FROM produk) as total_produk,
    (SELECT SUM(total_harga) FROM penjualan) as total_penjualan,
    (SELECT SUM(stok) FROM inventory) as total_stok;

-- 15. Create View untuk Sales per Cabang
CREATE OR REPLACE VIEW view_sales_per_cabang AS
SELECT 
    c.cabang_id,
    c.kode_cabang,
    c.nama_cabang,
    c.kota,
    COUNT(DISTINCT p.penjualan_id) as total_transaksi,
    COALESCE(SUM(p.total_harga), 0) as total_penjualan,
    COUNT(DISTINCT r.reseller_id) as jumlah_reseller
FROM cabang c
LEFT JOIN penjualan p ON c.cabang_id = p.cabang_id
LEFT JOIN reseller r ON c.cabang_id = r.cabang_id AND r.status = 'active'
GROUP BY c.cabang_id, c.kode_cabang, c.nama_cabang, c.kota;

-- 16. Create View untuk Stock per Cabang
CREATE OR REPLACE VIEW view_stock_per_cabang AS
SELECT 
    c.cabang_id,
    c.kode_cabang,
    c.nama_cabang,
    COUNT(DISTINCT i.produk_id) as jumlah_produk,
    SUM(i.stok) as total_stok,
    SUM(i.stok * pr.harga) as nilai_stok
FROM cabang c
LEFT JOIN inventory i ON c.cabang_id = i.cabang_id
LEFT JOIN produk pr ON i.produk_id = pr.produk_id
GROUP BY c.cabang_id, c.kode_cabang, c.nama_cabang;

-- 17. Create View untuk Reseller Performance
CREATE OR REPLACE VIEW view_reseller_performance AS
SELECT 
    r.reseller_id,
    r.kode_reseller,
    r.nama_reseller,
    r.nama_perusahaan,
    c.nama_cabang,
    COUNT(p.penjualan_id) as total_transaksi,
    COALESCE(SUM(p.total_harga), 0) as total_pembelian,
    r.status
FROM reseller r
LEFT JOIN cabang c ON r.cabang_id = c.cabang_id
LEFT JOIN penjualan p ON r.reseller_id = p.reseller_id
GROUP BY r.reseller_id, r.kode_reseller, r.nama_reseller, r.nama_perusahaan, c.nama_cabang, r.status;

-- 18. Create Stored Procedure untuk Get Sales by Date Range
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_get_sales_by_daterange(
    IN start_date DATE,
    IN end_date DATE,
    IN branch_id INT
)
BEGIN
    IF branch_id IS NULL THEN
        SELECT 
            p.penjualan_id,
            p.tanggal_penjualan,
            c.nama_cabang,
            pel.nama as nama_pelanggan,
            u.full_name as sales_person,
            r.nama_reseller,
            p.total_harga,
            p.status_pembayaran
        FROM penjualan p
        LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
        LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.pelanggan_id
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN reseller r ON p.reseller_id = r.reseller_id
        WHERE p.tanggal_penjualan BETWEEN start_date AND end_date
        ORDER BY p.tanggal_penjualan DESC;
    ELSE
        SELECT 
            p.penjualan_id,
            p.tanggal_penjualan,
            c.nama_cabang,
            pel.nama as nama_pelanggan,
            u.full_name as sales_person,
            r.nama_reseller,
            p.total_harga,
            p.status_pembayaran
        FROM penjualan p
        LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
        LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.pelanggan_id
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN reseller r ON p.reseller_id = r.reseller_id
        WHERE p.tanggal_penjualan BETWEEN start_date AND end_date
        AND p.cabang_id = branch_id
        ORDER BY p.tanggal_penjualan DESC;
    END IF;
END //
DELIMITER ;

-- 19. Create table untuk Audit Log
CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. Create indexes untuk performance
CREATE INDEX idx_cabang_status ON cabang(status);
CREATE INDEX idx_reseller_status ON reseller(status);
CREATE INDEX idx_reseller_cabang ON reseller(cabang_id);
CREATE INDEX idx_penjualan_cabang ON penjualan(cabang_id);
CREATE INDEX idx_penjualan_reseller ON penjualan(reseller_id);
CREATE INDEX idx_penjualan_tanggal ON penjualan(tanggal_penjualan);
CREATE INDEX idx_inventory_cabang ON inventory(cabang_id);
CREATE INDEX idx_audit_log_user ON audit_log(user_id);
CREATE INDEX idx_audit_log_table ON audit_log(table_name);

-- Selesai! Database sudah siap untuk fitur Administrator
SELECT 'Database update completed successfully!' as status;
