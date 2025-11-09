-- Add kategori column to reseller table
USE sinar_telkom_dashboard;

-- Add kategori column
ALTER TABLE reseller 
ADD COLUMN kategori VARCHAR(50) AFTER nama_perusahaan;

-- Update existing resellers with default kategori
UPDATE reseller SET kategori = 'Retail' WHERE kategori IS NULL;

-- Add index for kategori
CREATE INDEX idx_reseller_kategori ON reseller(kategori);

SELECT 'Kategori column added to reseller table successfully!' as status;
