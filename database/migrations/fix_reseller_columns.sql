-- Fix: Tambah dan update kolom kategori di tabel reseller
-- Kolom kategori harus berisi enum: Sales Force, General Manager, Manager, Supervisor, Player/Pemain, Merchant
-- Kolom contact_person harus ada

USE sinar_telkom_dashboard;

-- Cek struktur tabel reseller sebelum modifikasi
-- DESCRIBE reseller;

-- Tambah kolom kategori jika belum ada (sebagai VARCHAR dulu, kemudian akan diubah ke ENUM)
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS kategori VARCHAR(50);

-- Tambah kolom contact_person jika belum ada
ALTER TABLE reseller ADD COLUMN IF NOT EXISTS contact_person VARCHAR(100);

-- Modifikasi kolom kategori menjadi ENUM dengan opsi yang benar
-- Jika kolom sudah ada sebagai VARCHAR, ubah menjadi ENUM
ALTER TABLE reseller MODIFY COLUMN kategori ENUM('Sales Force', 'General Manager', 'Manager', 'Supervisor', 'Player/Pemain', 'Merchant') DEFAULT NULL;

-- Tampilkan struktur tabel setelah perubahan
DESCRIBE reseller;

-- Tampilkan data reseller
SELECT * FROM reseller LIMIT 5;
