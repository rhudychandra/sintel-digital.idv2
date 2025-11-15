-- Create tables for Pengajuan Stock SA & VF

CREATE TABLE IF NOT EXISTS pengajuan_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  rs_type ENUM('sa','vf') NOT NULL DEFAULT 'sa',
  outlet_id INT NOT NULL,
  requester_id INT NOT NULL,
  jenis VARCHAR(50) NOT NULL,
  warehouse_id INT NOT NULL,
  total_qty INT NOT NULL DEFAULT 0,
  total_saldo DECIMAL(16,2) NOT NULL DEFAULT 0,
  created_by INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rs_type (rs_type),
  INDEX idx_tanggal (tanggal),
  INDEX idx_outlet (outlet_id),
  INDEX idx_requester (requester_id),
  INDEX idx_warehouse (warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pengajuan_stock_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pengajuan_id INT NOT NULL,
  produk_id INT NOT NULL,
  qty INT NOT NULL,
  harga DECIMAL(16,2) NOT NULL,
  nominal DECIMAL(16,2) NOT NULL,
  INDEX idx_pengajuan (pengajuan_id),
  INDEX idx_produk (produk_id),
  CONSTRAINT fk_pengajuan_items_header FOREIGN KEY (pengajuan_id) REFERENCES pengajuan_stock(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
