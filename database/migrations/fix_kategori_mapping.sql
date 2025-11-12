-- Migration: Convert produk.kategori ENUM values to new kategori_produk names
-- Safe to run multiple times (idempotent updates)
-- 1. Ensure produk.kategori is VARCHAR(100)
ALTER TABLE produk MODIFY COLUMN kategori VARCHAR(100) NOT NULL;

-- 2. Ensure kategori_produk contains required new categories (INSERT IGNORE style)
INSERT INTO kategori_produk (nama_kategori, deskripsi, icon, status)
VALUES
('Voucher Internet Lite','Voucher Fisik Internet Lite','🔖','active'),
('Voucher Internet ByU','Voucher Internet ByU','🩹','active'),
('Perdana Internet Lite','Perdana Internet Lite','📕','active'),
('Perdana Internet ByU','Perdana Internet ByU','📘','active'),
('LinkAja','Saldo LinkAja','💴','active'),
('Finpay','Saldo Finpay','💶','active'),
('Voucher Segel Lite','Voucher Segel Lite','♦️','active'),
('Voucher Segel ByU','Voucher Segel ByU','♠️','active'),
('Perdana Segel Red 0K','Perdana Segel Red 0K','📕','active'),
('Perdana Segel ByU 0K','Perdana Segel ByU 0K','📘','active'),
('Perdana Internet Special','Perdana Internet Special (Nomor Cantik 260GB)','💎','active')
ON DUPLICATE KEY UPDATE nama_kategori = VALUES(nama_kategori);

-- 3. Map old categories to chosen new categories (simple one-to-one mapping)
UPDATE produk SET kategori = 'Voucher Internet Lite' WHERE kategori = 'internet';
UPDATE produk SET kategori = 'Voucher Internet ByU' WHERE kategori = 'tv_cable';
UPDATE produk SET kategori = 'LinkAja' WHERE kategori = 'phone';
UPDATE produk SET kategori = 'Perdana Internet Lite' WHERE kategori = 'paket_bundling';
UPDATE produk SET kategori = 'Perdana Internet Special' WHERE kategori = 'enterprise';

-- 4. Add kategori_id column if not exists
ALTER TABLE produk ADD COLUMN IF NOT EXISTS kategori_id INT NULL AFTER kategori;
ALTER TABLE produk ADD INDEX IF NOT EXISTS idx_kategori_id (kategori_id);

-- 5. Backfill kategori_id
UPDATE produk p
JOIN kategori_produk k ON p.kategori = k.nama_kategori
SET p.kategori_id = k.kategori_id
WHERE p.kategori_id IS NULL;

-- 6. Verification queries (uncomment to run)
-- SELECT DISTINCT kategori FROM produk ORDER BY kategori;
-- SELECT COUNT(*) AS produk_without_kategori_id FROM produk WHERE kategori_id IS NULL;

-- Rollback notes:
-- To rollback category name changes:
-- UPDATE produk SET kategori = 'internet' WHERE kategori = 'Voucher Internet Lite';
-- UPDATE produk SET kategori = 'tv_cable' WHERE kategori = 'Voucher Internet ByU';
-- UPDATE produk SET kategori = 'phone' WHERE kategori = 'LinkAja';
-- UPDATE produk SET kategori = 'paket_bundling' WHERE kategori = 'Perdana Internet Lite';
-- UPDATE produk SET kategori = 'enterprise' WHERE kategori = 'Perdana Internet Special';
-- (Foreign mapping for others not defined in original ENUM set.)
