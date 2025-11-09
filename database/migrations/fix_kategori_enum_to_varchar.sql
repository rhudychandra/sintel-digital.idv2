-- Fix kategori column from ENUM to VARCHAR to allow custom categories
-- Run this in phpMyAdmin or MySQL client

USE sinar_telkom_dashboard;

-- Change kategori column from ENUM to VARCHAR(100)
ALTER TABLE produk 
MODIFY COLUMN kategori VARCHAR(100) NOT NULL;

-- Verify the change
DESCRIBE produk;

-- Show success message
SELECT 'Kategori column successfully changed from ENUM to VARCHAR!' as status;
