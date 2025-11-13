-- =====================================================
-- CREATE OUTLET TABLE
-- =====================================================
-- Table untuk mengelola data outlet/toko retail
-- Termasuk informasi lokasi, pemilik, dan klasifikasi outlet
-- =====================================================

CREATE TABLE IF NOT EXISTS outlet (
    outlet_id INT AUTO_INCREMENT PRIMARY KEY,
    nama_outlet VARCHAR(255) NOT NULL COMMENT 'Nama toko/outlet',
    nomor_rs VARCHAR(50) UNIQUE NOT NULL COMMENT 'Nomor registrasi outlet',
    id_digipos VARCHAR(100) COMMENT 'ID dari sistem Digipos',
    nik_ktp VARCHAR(20) COMMENT 'NIK KTP pemilik (boleh kosong)',
    kelurahan_desa VARCHAR(100) COMMENT 'Kelurahan atau Desa',
    kecamatan VARCHAR(100) COMMENT 'Kecamatan',
    city VARCHAR(100) COMMENT 'Kota/Kabupaten',
    nama_pemilik VARCHAR(255) NOT NULL COMMENT 'Nama pemilik outlet',
    nomor_hp_pemilik VARCHAR(20) NOT NULL COMMENT 'Nomor HP pemilik',
    type_outlet VARCHAR(100) COMMENT 'Tipe outlet (Retail, Grosir, dll)',
    jadwal_kategori VARCHAR(50) COMMENT 'Kategori jadwal kunjungan',
    hari VARCHAR(50) COMMENT 'Hari kunjungan',
    sales_force_id INT COMMENT 'ID sales force dari tabel reseller',
    cabang_id INT COMMENT 'ID cabang',
    status_outlet ENUM('PJP', 'Non PJP') DEFAULT 'Non PJP' COMMENT 'Status PJP (Productive Journey Plan)',
    jenis_rs ENUM('Retail', 'Pareto', 'RS Eksekusi Voucher', 'RS Eksekusi SA') DEFAULT 'Retail' COMMENT 'Jenis klasifikasi outlet',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (sales_force_id) REFERENCES reseller(reseller_id) ON DELETE SET NULL,
    FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_nomor_rs (nomor_rs),
    INDEX idx_sales_force (sales_force_id),
    INDEX idx_cabang (cabang_id),
    INDEX idx_status_outlet (status_outlet),
    INDEX idx_jenis_rs (jenis_rs),
    INDEX idx_city (city),
    INDEX idx_kecamatan (kecamatan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO outlet (nama_outlet, nomor_rs, id_digipos, nik_ktp, kelurahan_desa, kecamatan, city, nama_pemilik, nomor_hp_pemilik, type_outlet, jadwal_kategori, hari, sales_force_id, cabang_id, status_outlet, jenis_rs) VALUES
('Toko Sinar Jaya', 'RS-001', 'DGP-001', '3201012345678901', 'Sukajadi', 'Bandung Wetan', 'Bandung', 'Budi Santoso', '081234567890', 'Retail', 'Kategori A', 'Senin', NULL, NULL, 'PJP', 'Retail'),
('Warung Maju Makmur', 'RS-002', 'DGP-002', NULL, 'Cicaheum', 'Kiaracondong', 'Bandung', 'Siti Aminah', '082345678901', 'Grosir', 'Kategori B', 'Selasa', NULL, NULL, 'PJP', 'Pareto'),
('Counter Cell Sejahtera', 'RS-003', 'DGP-003', '3201023456789012', 'Antapani', 'Antapani', 'Bandung', 'Agus Wijaya', '083456789012', 'Retail', 'Kategori A', 'Rabu', NULL, NULL, 'Non PJP', 'RS Eksekusi Voucher');

-- Verification query
SELECT 
    'OUTLET' as Tabel,
    COUNT(*) as Total_Outlet,
    COUNT(CASE WHEN status_outlet = 'PJP' THEN 1 END) as Total_PJP,
    COUNT(CASE WHEN status_outlet = 'Non PJP' THEN 1 END) as Total_Non_PJP,
    COUNT(CASE WHEN jenis_rs = 'Retail' THEN 1 END) as Total_Retail,
    COUNT(CASE WHEN jenis_rs = 'Pareto' THEN 1 END) as Total_Pareto,
    COUNT(CASE WHEN sales_force_id IS NOT NULL THEN 1 END) as Total_With_Sales
FROM outlet;

-- Show all outlets
SELECT 
    outlet_id,
    nama_outlet,
    nomor_rs,
    city,
    nama_pemilik,
    nomor_hp_pemilik,
    status_outlet,
    jenis_rs
FROM outlet
ORDER BY created_at DESC;

-- =====================================================
-- Run this SQL in phpMyAdmin or MySQL client
-- Jika melihat data outlet, maka SUCCESS! ✅
-- =====================================================
