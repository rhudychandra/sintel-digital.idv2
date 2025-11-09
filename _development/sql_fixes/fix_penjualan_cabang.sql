-- Fix: Update penjualan records to have cabang_id
-- This will assign cabang_id based on the user who created the sale

USE sinar_telkom_dashboard;

-- Update penjualan with cabang_id from users table
UPDATE penjualan p
INNER JOIN users u ON p.user_id = u.user_id
SET p.cabang_id = u.cabang_id
WHERE p.cabang_id IS NULL OR p.cabang_id = 0;

-- If you want to set all existing penjualan to cabang_id = 1 (for testing)
-- Uncomment the line below:
-- UPDATE penjualan SET cabang_id = 1 WHERE cabang_id IS NULL OR cabang_id = 0;

-- Verify the update
SELECT 
    p.penjualan_id,
    p.no_invoice,
    p.tanggal_penjualan,
    p.cabang_id,
    c.nama_cabang,
    u.full_name as created_by
FROM penjualan p
LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
LEFT JOIN users u ON p.user_id = u.user_id
ORDER BY p.penjualan_id DESC
LIMIT 10;
