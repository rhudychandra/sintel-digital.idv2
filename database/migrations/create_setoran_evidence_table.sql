-- Create setoran_evidence table for storing deposit evidence uploads
USE sinar_telkom_dashboard;

CREATE TABLE IF NOT EXISTS setoran_evidence (
    evidence_id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    cabang VARCHAR(100) NOT NULL,
    atas_nama VARCHAR(150) NOT NULL,
    bank VARCHAR(100) NOT NULL,
    evidence_path VARCHAR(255) NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_tanggal (tanggal),
    INDEX idx_cabang (cabang),
    INDEX idx_bank (bank)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification
SELECT 'Table setoran_evidence created (or exists)' AS status;
DESCRIBE setoran_evidence;
