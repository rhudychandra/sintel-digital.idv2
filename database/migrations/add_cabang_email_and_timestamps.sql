-- Add missing columns to cabang table to match application and import dumps
-- Run this in phpMyAdmin on your LOCAL database (e.g., sinar_telkom_dashboard)

USE sinar_telkom_dashboard;

-- Add email column if it doesn't exist
ALTER TABLE cabang
  ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL AFTER telepon;

-- Add manager_name if missing (safety)
ALTER TABLE cabang
  ADD COLUMN IF NOT EXISTS manager_name VARCHAR(100) NULL AFTER email;

-- Add telepon if missing (safety)
ALTER TABLE cabang
  ADD COLUMN IF NOT EXISTS telepon VARCHAR(20) NULL AFTER provinsi;

-- Add timestamps if they don't exist (to support dumps that include them)
ALTER TABLE cabang
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Verify structure
DESCRIBE cabang;
