-- =====================================================
-- ADD HPP (Harga Pokok Penjualan) TO PRODUK TABLE
-- =====================================================
-- This migration adds HPP column to track cost price
-- HPP = Cost price (what we pay to supplier)
-- Harga = Selling price (what customer pays)
-- =====================================================

-- Add HPP column to produk table
ALTER TABLE produk 
ADD COLUMN hpp DECIMAL(15,2) DEFAULT 0 COMMENT 'Harga Pokok Penjualan (cost price)' AFTER harga,
ADD COLUMN profit_margin DECIMAL(5,2) DEFAULT 0 COMMENT 'Profit margin percentage: ((harga-hpp)/hpp)*100' AFTER hpp;

-- Add index for HPP queries
CREATE INDEX IF NOT EXISTS idx_produk_hpp ON produk(hpp);

-- Update existing products with default HPP (80% of selling price as example)
UPDATE produk 
SET hpp = ROUND(harga * 0.80, 2)
WHERE hpp = 0 OR hpp IS NULL;

-- Update profit margin for existing products
UPDATE produk 
SET profit_margin = ROUND(((harga - hpp) / hpp) * 100, 2)
WHERE hpp > 0;

-- Verification query
SELECT 
    'PRODUK' as Tabel,
    COUNT(*) as Total_Produk,
    COUNT(CASE WHEN hpp > 0 THEN 1 END) as Produk_With_HPP,
    ROUND(AVG(hpp), 2) as Avg_HPP,
    ROUND(AVG(harga), 2) as Avg_Harga_Jual,
    ROUND(AVG(profit_margin), 2) as Avg_Margin_Persen
FROM produk;

-- Show sample products with HPP
SELECT 
    produk_id,
    kode_produk,
    nama_produk,
    FORMAT(hpp, 0, 'id_ID') as HPP,
    FORMAT(harga, 0, 'id_ID') as Harga_Jual,
    CONCAT(FORMAT(profit_margin, 1), '%') as Margin
FROM produk 
LIMIT 10;

-- =====================================================
-- Jika melihat data HPP dan margin, maka SUCCESS! ✅
-- =====================================================
