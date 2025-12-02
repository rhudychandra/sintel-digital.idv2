-- Combined Import SQL for current features
-- Target: MySQL 5.7+/8.0
-- Usage:
--   1) Create/select your database manually (e.g., sinar_telkom_dashboard)
--   2) Run this file in MySQL client:
--        mysql -u root -p sinar_telkom_dashboard < combined_import.sql
--      or import via phpMyAdmin

-- Ensure correct database is selected
-- REPLACE below with your DB name if different
USE sinar_telkom_dashboard;

-- =============================
-- Core reference tables (minimal)
-- =============================
CREATE TABLE IF NOT EXISTS cabang (
  cabang_id INT AUTO_INCREMENT PRIMARY KEY,
  nama_cabang VARCHAR(150) NOT NULL,
  email VARCHAR(150),
  status ENUM('active','inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reseller (
  reseller_id INT AUTO_INCREMENT PRIMARY KEY,
  nama_reseller VARCHAR(150) NOT NULL,
  cabang_id INT,
  kategori VARCHAR(100),
  status ENUM('active','inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cabang (cabang_id),
  CONSTRAINT fk_reseller_cabang FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS outlet (
  outlet_id INT AUTO_INCREMENT PRIMARY KEY,
  nama_outlet VARCHAR(200) NOT NULL,
  nomor_rs VARCHAR(100),
  id_digipos VARCHAR(100),
  kabupaten VARCHAR(150),
  type_outlet VARCHAR(100),
  jenis_rs VARCHAR(150),
  status ENUM('active','inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Users (ensure roles)
-- =============================
-- Add missing roles by widening ENUM or converting to VARCHAR
SET @has_users := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='users');
SET @role_is_enum := (SELECT DATA_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='users' AND column_name='role');
-- Convert role to VARCHAR if needed for extended roles
SET @sql := NULL;
SELECT CASE WHEN @has_users = 1 AND @role_is_enum='enum' THEN CONCAT('ALTER TABLE users MODIFY COLUMN role VARCHAR(32) NOT NULL DEFAULT \"staff\"') ELSE NULL END INTO @sql;
SET @sql = IFNULL(@sql,'');
SET @sql := IF(@sql='', NULL, @sql);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================
-- Produk adjustments (HPP Saldo)
-- =============================
-- Add hpp_saldo/hpp_fisik columns if missing and relax kategori to VARCHAR
SET @has_produk := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='produk');
SET @has_hpp_saldo := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='produk' AND column_name='hpp_saldo');
SET @has_hpp_fisik := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='produk' AND column_name='hpp_fisik');
SET @is_kategori_enum := (SELECT DATA_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='produk' AND column_name='kategori');

SET @sql := NULL;
SELECT CASE WHEN @has_produk=1 AND @has_hpp_saldo=0 THEN 'ALTER TABLE produk ADD COLUMN hpp_saldo DECIMAL(16,2) NULL AFTER harga' END INTO @sql;
SET @sql := IFNULL(@sql,'');
SET @sql2 := NULL; SELECT CASE WHEN @has_produk=1 AND @has_hpp_fisik=0 THEN 'ALTER TABLE produk ADD COLUMN hpp_fisik DECIMAL(16,2) NULL AFTER hpp_saldo' END INTO @sql2;
SET @sql3 := NULL; SELECT CASE WHEN @has_produk=1 AND @is_kategori_enum='enum' THEN 'ALTER TABLE produk MODIFY COLUMN kategori VARCHAR(150) NOT NULL' END INTO @sql3;

SET @sql := IF(@sql='', NULL, @sql); SET @sql2 := IF(@sql2='', NULL, @sql2); SET @sql3 := IF(@sql3='', NULL, @sql3);
PREPARE s1 FROM @sql; EXECUTE s1; DEALLOCATE PREPARE s1;
PREPARE s2 FROM @sql2; EXECUTE s2; DEALLOCATE PREPARE s2;
PREPARE s3 FROM @sql3; EXECUTE s3; DEALLOCATE PREPARE s3;

-- =============================
-- Inventory adjustments (branch + approval)
-- =============================
SET @has_inventory := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='inventory');
SET @has_inv_cabang := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='inventory' AND column_name='cabang_id');
SET @has_inv_approval := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='inventory' AND column_name='status_approval');

SET @sql := NULL; SELECT CASE WHEN @has_inventory=1 AND @has_inv_cabang=0 THEN 'ALTER TABLE inventory ADD COLUMN cabang_id INT NULL AFTER user_id' END INTO @sql;
SET @sql2 := NULL; SELECT CASE WHEN @has_inventory=1 AND @has_inv_approval=0 THEN 'ALTER TABLE inventory ADD COLUMN status_approval ENUM(\'pending\',\'approved\',\'rejected\') DEFAULT \'pending\' AFTER keterangan' END INTO @sql2;

SET @sql := IF(@sql='', NULL, @sql); SET @sql2 := IF(@sql2='', NULL, @sql2);
PREPARE i1 FROM @sql; EXECUTE i1; DEALLOCATE PREPARE i1;
PREPARE i2 FROM @sql2; EXECUTE i2; DEALLOCATE PREPARE i2;

-- Foreign key for cabang_id if not exists
ALTER TABLE inventory
  ADD INDEX IF NOT EXISTS idx_inventory_cabang (cabang_id);
-- MySQL lacks IF NOT EXISTS for ADD CONSTRAINT; wrap in try-catch by checking information_schema
SET @has_fk := (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE table_schema = DATABASE() AND table_name='inventory' AND constraint_name='fk_inventory_cabang');
SET @sql := NULL; SELECT CASE WHEN @has_fk=0 THEN 'ALTER TABLE inventory ADD CONSTRAINT fk_inventory_cabang FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL' END INTO @sql;
SET @sql := IF(@sql='', NULL, @sql);
PREPARE i3 FROM @sql; EXECUTE i3; DEALLOCATE PREPARE i3;

-- =============================
-- Pengajuan Stock SA & VF tables
-- =============================
CREATE TABLE IF NOT EXISTS pengajuan_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  rs_type ENUM('sa','vf') NOT NULL DEFAULT 'sa',
  outlet_id INT NOT NULL,
  requester_id INT NOT NULL,
  jenis VARCHAR(50) NOT NULL,
  warehouse_id INT NOT NULL,
  total_qty INT NOT NULL DEFAULT 0,
  total_saldo DECIMAL(16,2) NOT NULL DEFAULT 0,
  created_by INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rs_type (rs_type),
  INDEX idx_tanggal (tanggal),
  INDEX idx_outlet (outlet_id),
  INDEX idx_requester (requester_id),
  INDEX idx_warehouse (warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pengajuan_stock_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pengajuan_id INT NOT NULL,
  produk_id INT NOT NULL,
  qty INT NOT NULL,
  harga DECIMAL(16,2) NOT NULL,
  nominal DECIMAL(16,2) NOT NULL,
  INDEX idx_pengajuan (pengajuan_id),
  INDEX idx_produk (produk_id),
  CONSTRAINT fk_pengajuan_items_header FOREIGN KEY (pengajuan_id) REFERENCES pengajuan_stock(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Minimal seed data (optional)
-- =============================
INSERT INTO cabang (nama_cabang, email, status) VALUES
('Warehouse Lubuklinggau', NULL, 'active'),
('Warehouse Lahat', NULL, 'active')
ON DUPLICATE KEY UPDATE nama_cabang=VALUES(nama_cabang);

-- Administrator user (if not exists)
INSERT INTO users (username, password, full_name, email, role, status)
SELECT 'administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'administrator@example.com', 'administrator', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='administrator');

-- Done
