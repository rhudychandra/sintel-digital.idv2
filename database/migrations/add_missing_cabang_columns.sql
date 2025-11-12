-- Fix: Tambah kolom yang mungkin hilang di tabel cabang
-- Jalankan query ini jika kolom telepon dan manager_name belum ada

USE sinar_telkom_dashboard;

-- Cek dan tambah kolom manager_name jika belum ada
ALTER TABLE cabang ADD COLUMN IF NOT EXISTS manager_name VARCHAR(100);

-- Cek dan tambah kolom telepon jika belum ada
ALTER TABLE cabang ADD COLUMN IF NOT EXISTS telepon VARCHAR(20);

-- Tampilkan struktur tabel setelah perubahan
DESCRIBE cabang;
