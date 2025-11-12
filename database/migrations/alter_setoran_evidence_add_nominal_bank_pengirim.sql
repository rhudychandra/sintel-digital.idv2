-- Alter setoran_evidence to add nominal and bank_pengirim columns
USE sinar_telkom_dashboard;

-- Add columns if they don't exist
ALTER TABLE setoran_evidence
  ADD COLUMN IF NOT EXISTS nominal DECIMAL(15,2) NULL AFTER bank,
  ADD COLUMN IF NOT EXISTS bank_pengirim VARCHAR(100) NULL AFTER atas_nama;

-- Optional indexes
CREATE INDEX IF NOT EXISTS idx_setoran_evidence_nominal ON setoran_evidence(nominal);
CREATE INDEX IF NOT EXISTS idx_setoran_evidence_bank_pengirim ON setoran_evidence(bank_pengirim);

-- Verify
DESCRIBE setoran_evidence;
