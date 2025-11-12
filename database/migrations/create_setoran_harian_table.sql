-- Create setoran_harian table for finance module
-- This table stores daily deposits/setoran from resellers

USE sinar_telkom_dashboard;

-- Create setoran_harian table
CREATE TABLE IF NOT EXISTS setoran_harian (
    setoran_id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    cabang VARCHAR(100),
    reseller VARCHAR(100),
    produk VARCHAR(200),
    qty INT NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    total_setoran DECIMAL(15,2) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_tanggal (tanggal),
    INDEX idx_cabang (cabang),
    INDEX idx_reseller (reseller),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create additional index for date range queries
CREATE INDEX idx_tanggal_cabang ON setoran_harian(tanggal, cabang);
CREATE INDEX idx_tanggal_reseller ON setoran_harian(tanggal, reseller);

-- Verification
SELECT 'Table setoran_harian created successfully!' as status;
SELECT COUNT(*) as total_records FROM setoran_harian;
