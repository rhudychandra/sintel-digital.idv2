-- Check if inventory table has cabang_id column
DESCRIBE inventory;

-- If cabang_id doesn't exist, add it
ALTER TABLE inventory ADD COLUMN IF NOT EXISTS cabang_id INT AFTER user_id;
ALTER TABLE inventory ADD CONSTRAINT fk_inventory_cabang FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id);
