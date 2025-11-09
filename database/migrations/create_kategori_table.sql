-- Create kategori_produk table for managing product categories
USE sinar_telkom_dashboard;

-- Create kategori_produk table
CREATE TABLE IF NOT EXISTS kategori_produk (
    kategori_id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    icon VARCHAR(50) DEFAULT '📦',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_nama (nama_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO kategori_produk (nama_kategori, deskripsi, icon) VALUES
('Internet', 'Paket internet dan broadband', '🌐'),
('TV Cable', 'Layanan TV kabel dan streaming', '📺'),
('Phone', 'Layanan telepon dan komunikasi', '📞'),
('Paket Bundling', 'Paket kombinasi layanan', '📦'),
('Enterprise', 'Solusi untuk perusahaan', '🏢')
ON DUPLICATE KEY UPDATE nama_kategori=nama_kategori;

-- Change produk.kategori from ENUM to VARCHAR if not already
ALTER TABLE produk 
MODIFY COLUMN kategori VARCHAR(100) NOT NULL;

-- Add foreign key relationship (optional, for data integrity)
-- Note: This will fail if there are existing products with categories not in kategori_produk
-- ALTER TABLE produk ADD CONSTRAINT fk_produk_kategori 
-- FOREIGN KEY (kategori) REFERENCES kategori_produk(nama_kategori) 
-- ON UPDATE CASCADE ON DELETE RESTRICT;

SELECT 'Kategori table created successfully!' as status;
SELECT * FROM kategori_produk;
