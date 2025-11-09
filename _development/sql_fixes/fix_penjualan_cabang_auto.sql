-- ============================================
-- FIX: Auto-assign cabang_id untuk penjualan
-- ============================================

USE sinar_telkom_dashboard;

-- 1. Update semua penjualan yang cabang_id-nya NULL
--    Ambil cabang_id dari user yang membuat penjualan
UPDATE penjualan p
INNER JOIN users u ON p.user_id = u.user_id
SET p.cabang_id = u.cabang_id
WHERE p.cabang_id IS NULL 
AND u.cabang_id IS NOT NULL;

-- 2. Jika masih ada yang NULL (user tidak punya cabang_id),
--    set ke cabang_id = 1 (default)
UPDATE penjualan 
SET cabang_id = 1 
WHERE cabang_id IS NULL;

-- 3. Verify hasil update
SELECT 
    'BEFORE UPDATE' as status,
    COUNT(*) as total_null
FROM penjualan 
WHERE cabang_id IS NULL;

SELECT 
    'AFTER UPDATE' as status,
    cabang_id,
    COUNT(*) as jumlah_transaksi,
    SUM(total) as total_nilai
FROM penjualan
GROUP BY cabang_id
ORDER BY cabang_id;

-- 4. Show latest 10 records
SELECT 
    p.penjualan_id,
    p.no_invoice,
    p.tanggal_penjualan,
    p.cabang_id,
    c.nama_cabang,
    u.full_name as created_by,
    p.total,
    p.created_at
FROM penjualan p
LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
LEFT JOIN users u ON p.user_id = u.user_id
ORDER BY p.penjualan_id DESC
LIMIT 10;
