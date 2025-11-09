-- Fix untuk Blank Screen di Menu Users
-- Jalankan file ini jika menu users masih blank screen

USE sinar_telkom_dashboard;

-- 1. Pastikan tabel cabang ada
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

-- 2. Insert sample cabang jika belum ada
INSERT IGNORE INTO cabang (kode_cabang, nama_cabang, alamat, kota, provinsi, telepon, email, manager_name, status) VALUES
('JKT001', 'Cabang Jakarta Pusat', 'Jl. Sudirman No. 123', 'Jakarta', 'DKI Jakarta', '021-12345678', 'jakarta@sinartelkom.com', 'Budi Santoso', 'active'),
('BDG001', 'Cabang Bandung', 'Jl. Asia Afrika No. 45', 'Bandung', 'Jawa Barat', '022-87654321', 'bandung@sinartelkom.com', 'Siti Nurhaliza', 'active'),
('SBY001', 'Cabang Surabaya', 'Jl. Tunjungan No. 67', 'Surabaya', 'Jawa Timur', '031-23456789', 'surabaya@sinartelkom.com', 'Ahmad Yani', 'active');

-- 3. Tambah kolom cabang_id ke tabel users jika belum ada
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'cabang_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT NULL AFTER role')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 4. Verify tables
SELECT 'Checking tables...' as status;
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'OK - Tabel cabang ada'
        ELSE 'ERROR - Tabel cabang tidak ada'
    END as cabang_table_status
FROM information_schema.tables 
WHERE table_schema = 'sinar_telkom_dashboard' 
AND table_name = 'cabang';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'OK - Kolom cabang_id ada di tabel users'
        ELSE 'ERROR - Kolom cabang_id tidak ada di tabel users'
    END as cabang_id_column_status
FROM information_schema.columns 
WHERE table_schema = 'sinar_telkom_dashboard' 
AND table_name = 'users' 
AND column_name = 'cabang_id';

-- 5. Check data
SELECT COUNT(*) as total_cabang FROM cabang;
SELECT COUNT(*) as total_users FROM users;

SELECT 'Fix completed! Silakan test menu users lagi.' as status;
