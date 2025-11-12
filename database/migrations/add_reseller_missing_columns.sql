-- Fix: Tambah kolom yang mungkin hilang di tabel reseller
-- Tabel reseller harus memiliki semua kolom berikut:
-- kode_reseller, nama_reseller, nama_perusahaan, alamat, kota, provinsi, 
-- telepon, email, contact_person, kategori, cabang_id, status, total_penjualan

USE sinar_telkom_dashboard;

-- Tambah kolom yang mungkin belum ada di tabel reseller
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS kode_reseller VARCHAR(20) UNIQUE;
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS nama_reseller VARCHAR(100);
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS nama_perusahaan VARCHAR(100);
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS alamat TEXT;
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS kota VARCHAR(50);
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS provinsi VARCHAR(50);
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS telepon VARCHAR(20);
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS email VARCHAR(100);
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS contact_person VARCHAR(100);
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS kategori ENUM('Sales Force', 'General Manager', 'Manager', 'Supervisor', 'Player/Pemain', 'Merchant') DEFAULT NULL;
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS cabang_id INT;
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS total_penjualan DECIMAL(15,2) DEFAULT 0;
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Tambah foreign key jika belum ada (optional, untuk data integrity)
-- ALTER TABLE reseller ADD CONSTRAINT fk_reseller_cabang 
--     FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL;

-- Tampilkan struktur tabel setelah perubahan
DESCRIBE reseller;

-- Tampilkan data reseller
SELECT * FROM reseller LIMIT 10;
