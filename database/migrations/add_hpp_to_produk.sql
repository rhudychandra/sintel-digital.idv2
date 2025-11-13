-- =====================================================
-- ADD HPP (Harga Pokok Penjualan) TO PRODUK TABLE
-- =====================================================
-- This migration adds HPP columns to track cost price
-- HPP Saldo = Cost price for balance/virtual products
-- HPP Fisik = Cost price for physical products
-- Harga = Selling price (what customer pays)
-- =====================================================

-- Drop old column if exists (from previous version)
ALTER TABLE produk 
DROP COLUMN IF EXISTS hpp,
DROP COLUMN IF EXISTS profit_margin;

-- Add new HPP columns to produk table
ALTER TABLE produk 
ADD COLUMN hpp_saldo DECIMAL(15,2) DEFAULT 0 COMMENT 'HPP untuk produk saldo/virtual' AFTER harga,
ADD COLUMN hpp_fisik DECIMAL(15,2) DEFAULT 0 COMMENT 'HPP untuk produk fisik' AFTER hpp_saldo,
ADD COLUMN profit_margin_saldo DECIMAL(5,2) DEFAULT 0 COMMENT 'Margin saldo: ((harga-hpp_saldo)/hpp_saldo)*100' AFTER hpp_fisik,
ADD COLUMN profit_margin_fisik DECIMAL(5,2) DEFAULT 0 COMMENT 'Margin fisik: ((harga-hpp_fisik)/hpp_fisik)*100' AFTER profit_margin_saldo;

-- Add indexes for HPP queries
CREATE INDEX IF NOT EXISTS idx_produk_hpp_saldo ON produk(hpp_saldo);
CREATE INDEX IF NOT EXISTS idx_produk_hpp_fisik ON produk(hpp_fisik);

-- Update existing products with default HPP (80% of selling price as example)
UPDATE produk 
SET hpp_saldo = ROUND(harga * 0.80, 2),
    hpp_fisik = ROUND(harga * 0.85, 2)
WHERE hpp_saldo = 0 OR hpp_saldo IS NULL;

-- Update profit margin for existing products
UPDATE produk 
SET profit_margin_saldo = ROUND(((harga - hpp_saldo) / hpp_saldo) * 100, 2)
WHERE hpp_saldo > 0;

UPDATE produk 
SET profit_margin_fisik = ROUND(((harga - hpp_fisik) / hpp_fisik) * 100, 2)
WHERE hpp_fisik > 0;

-- Verification query
SELECT 
    'PRODUK' as Tabel,
    COUNT(*) as Total_Produk,
    COUNT(CASE WHEN hpp_saldo > 0 THEN 1 END) as Produk_With_HPP_Saldo,
    COUNT(CASE WHEN hpp_fisik > 0 THEN 1 END) as Produk_With_HPP_Fisik,
    ROUND(AVG(hpp_saldo), 2) as Avg_HPP_Saldo,
    ROUND(AVG(hpp_fisik), 2) as Avg_HPP_Fisik,
    ROUND(AVG(harga), 2) as Avg_Harga_Jual,
    ROUND(AVG(profit_margin_saldo), 2) as Avg_Margin_Saldo,
    ROUND(AVG(profit_margin_fisik), 2) as Avg_Margin_Fisik
FROM produk;

-- Show sample products with HPP
SELECT 
    produk_id,
    kode_produk,
    nama_produk,
    FORMAT(hpp_saldo, 0, 'id_ID') as HPP_Saldo,
    FORMAT(hpp_fisik, 0, 'id_ID') as HPP_Fisik,
    FORMAT(harga, 0, 'id_ID') as Harga_Jual,
    CONCAT(FORMAT(profit_margin_saldo, 1), '%') as Margin_Saldo,
    CONCAT(FORMAT(profit_margin_fisik, 1), '%') as Margin_Fisik
FROM produk 
LIMIT 10;

-- =====================================================
-- Jika melihat data HPP dan margin, maka SUCCESS! ✅
-- =====================================================
