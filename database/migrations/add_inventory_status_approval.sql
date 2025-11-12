-- Fix: Tambah kolom status_approval di tabel inventory
-- Kolom ini digunakan untuk sistem approval inventory

USE sinar_telkom_dashboard;

-- Tambah kolom status_approval jika belum ada
ALTER TABLE inventory ADD COLUMN IF NOT EXISTS status_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';

-- Jika kolom sudah ada dengan tipe berbeda, uncomment baris di bawah untuk update tipe:
-- ALTER TABLE inventory MODIFY COLUMN status_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';

-- Update existing records yang belum memiliki nilai status_approval
UPDATE inventory SET status_approval = 'approved' WHERE status_approval IS NULL;

-- Tambah index untuk performa query yang lebih baik
ALTER TABLE inventory ADD INDEX IF NOT EXISTS idx_status_approval (status_approval);

-- Tampilkan struktur tabel inventory setelah perubahan
DESCRIBE inventory;

-- Verifikasi data
SELECT COUNT(*) as total_inventory, 
       COUNT(CASE WHEN status_approval = 'pending' THEN 1 END) as pending,
       COUNT(CASE WHEN status_approval = 'approved' THEN 1 END) as approved,
       COUNT(CASE WHEN status_approval = 'rejected' THEN 1 END) as rejected
FROM inventory;
