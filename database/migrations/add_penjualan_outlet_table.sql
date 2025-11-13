-- =====================================================
-- CREATE PENJUALAN OUTLET TABLE
-- =====================================================
-- Table untuk mencatat penjualan per outlet dari sales force
-- =====================================================

CREATE TABLE IF NOT EXISTS penjualan_outlet (
    penjualan_outlet_id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL COMMENT 'Tanggal penjualan',
    sales_force_id INT NOT NULL COMMENT 'ID sales force dari tabel reseller',
    produk_id INT NOT NULL COMMENT 'ID produk',
    outlet_id INT NOT NULL COMMENT 'ID outlet',
    qty INT NOT NULL COMMENT 'Jumlah produk terjual',
    nominal DECIMAL(15,2) NOT NULL COMMENT 'Total nominal penjualan',
    keterangan TEXT COMMENT 'Keterangan tambahan',
    cabang_id INT COMMENT 'ID cabang',
    created_by INT COMMENT 'User yang input',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (sales_force_id) REFERENCES reseller(reseller_id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(produk_id) ON DELETE CASCADE,
    FOREIGN KEY (outlet_id) REFERENCES outlet(outlet_id) ON DELETE CASCADE,
    FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_tanggal (tanggal),
    INDEX idx_sales_force (sales_force_id),
    INDEX idx_produk (produk_id),
    INDEX idx_outlet (outlet_id),
    INDEX idx_cabang (cabang_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification query
SELECT 'PENJUALAN_OUTLET' as Tabel, COUNT(*) as Total_Records FROM penjualan_outlet;

-- =====================================================
-- Run this SQL in phpMyAdmin or MySQL client
-- Jika tabel berhasil dibuat, maka SUCCESS! ✅
-- =====================================================
