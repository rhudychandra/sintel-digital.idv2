-- Create Views Only (No ALTER TABLE)
-- Untuk fix admin panel yang tidak menampilkan data
-- Fixed: Menggunakan kolom 'total' bukan 'total_harga' dan 'stok_sesudah' bukan 'stok'

USE sinar_telkom_dashboard;

-- 1. Create View untuk Admin Dashboard
CREATE OR REPLACE VIEW view_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM cabang WHERE status = 'active') as total_cabang,
    (SELECT COUNT(*) FROM reseller WHERE status = 'active') as total_reseller,
    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
    (SELECT COUNT(*) FROM produk) as total_produk,
    (SELECT COALESCE(SUM(total), 0) FROM penjualan WHERE status_pembayaran = 'paid') as total_penjualan,
    (SELECT COALESCE(SUM(stok), 0) FROM produk) as total_stok;

-- 2. Create View untuk Sales per Cabang
CREATE OR REPLACE VIEW view_sales_per_cabang AS
SELECT 
    c.cabang_id,
    c.kode_cabang,
    c.nama_cabang,
    c.kota,
    COUNT(DISTINCT p.penjualan_id) as total_transaksi,
    COALESCE(SUM(p.total), 0) as total_penjualan,
    COUNT(DISTINCT r.reseller_id) as jumlah_reseller
FROM cabang c
LEFT JOIN penjualan p ON c.cabang_id = p.cabang_id AND p.status_pembayaran = 'paid'
LEFT JOIN reseller r ON c.cabang_id = r.cabang_id AND r.status = 'active'
GROUP BY c.cabang_id, c.kode_cabang, c.nama_cabang, c.kota;

-- 3. Create View untuk Stock per Cabang
CREATE OR REPLACE VIEW view_stock_per_cabang AS
SELECT 
    c.cabang_id,
    c.kode_cabang,
    c.nama_cabang,
    COUNT(DISTINCT pr.produk_id) as jumlah_produk,
    COALESCE(SUM(pr.stok), 0) as total_stok,
    COALESCE(SUM(pr.stok * pr.harga), 0) as nilai_stok
FROM cabang c
LEFT JOIN produk pr ON c.cabang_id = pr.cabang_id
GROUP BY c.cabang_id, c.kode_cabang, c.nama_cabang;

-- 4. Create View untuk Reseller Performance
CREATE OR REPLACE VIEW view_reseller_performance AS
SELECT 
    r.reseller_id,
    r.kode_reseller,
    r.nama_reseller,
    r.nama_perusahaan,
    c.nama_cabang,
    COUNT(p.penjualan_id) as total_transaksi,
    COALESCE(SUM(p.total), 0) as total_pembelian,
    r.status
FROM reseller r
LEFT JOIN cabang c ON r.cabang_id = c.cabang_id
LEFT JOIN penjualan p ON r.reseller_id = p.reseller_id AND p.status_pembayaran = 'paid'
GROUP BY r.reseller_id, r.kode_reseller, r.nama_reseller, r.nama_perusahaan, c.nama_cabang, r.status;

-- Test all views
SELECT 'Testing view_admin_dashboard:' as test;
SELECT * FROM view_admin_dashboard;

SELECT 'Testing view_sales_per_cabang:' as test;
SELECT * FROM view_sales_per_cabang LIMIT 3;

SELECT 'Testing view_stock_per_cabang:' as test;
SELECT * FROM view_stock_per_cabang LIMIT 3;

SELECT 'Testing view_reseller_performance:' as test;
SELECT * FROM view_reseller_performance LIMIT 3;

SELECT '✅ All views created successfully!' as status;
