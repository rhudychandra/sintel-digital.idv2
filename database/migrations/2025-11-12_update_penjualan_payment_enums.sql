-- Migration: Update penjualan payment enums to new business values
-- Date: 2025-11-12
-- Safe and idempotent-ish: uses VARCHAR during data migration then tightens back to ENUM

START TRANSACTION;

-- 1) metode_pembayaran
-- Relax to VARCHAR to allow remapping values
ALTER TABLE penjualan MODIFY COLUMN metode_pembayaran VARCHAR(50) NOT NULL;

-- Map old -> new
-- Assumptions:
--   - 'transfer' -> 'Transfer'
--   - 'cash' -> 'Cash'
--   - 'e_wallet' -> 'Finpay' (Finpay sebagai aggregator e-wallet)
--   - 'credit_card' & 'debit_card' -> 'Transfer' (ASSUMPTION: ganti jika ingin ke 'Cash')
UPDATE penjualan SET metode_pembayaran = 'Transfer' WHERE LOWER(metode_pembayaran) IN ('transfer');
UPDATE penjualan SET metode_pembayaran = 'Cash' WHERE LOWER(metode_pembayaran) IN ('cash');
UPDATE penjualan SET metode_pembayaran = 'Finpay' WHERE LOWER(metode_pembayaran) IN ('e_wallet','e-wallet');
UPDATE penjualan SET metode_pembayaran = 'Transfer' WHERE LOWER(metode_pembayaran) IN ('credit_card','debit_card','credit card','debit card');

-- Constrain to new ENUM
ALTER TABLE penjualan MODIFY COLUMN metode_pembayaran ENUM('Transfer','Cash','Budget Komitmen','Finpay') NOT NULL DEFAULT 'Transfer';

-- 2) status_pembayaran
-- Relax to VARCHAR to allow remapping values
ALTER TABLE penjualan MODIFY COLUMN status_pembayaran VARCHAR(50) NOT NULL;

-- Map old -> new
-- Assumptions:
--   - 'paid' -> 'Paid (Lunas)'
--   - 'pending' -> 'Pending (Menunggu)'
--   - 'cancelled' -> 'Cancelled (Dibatalkan)'
--   - 'refunded' -> 'Cancelled (Dibatalkan)' (ASSUMPTION: tidak ada status Refund di set baru)
--   - existing 'TOP' variants -> 'TOP (Term Off Payment)'
UPDATE penjualan SET status_pembayaran = 'Paid (Lunas)' WHERE LOWER(status_pembayaran) = 'paid';
UPDATE penjualan SET status_pembayaran = 'Pending (Menunggu)' WHERE LOWER(status_pembayaran) = 'pending';
UPDATE penjualan SET status_pembayaran = 'Cancelled (Dibatalkan)' WHERE LOWER(status_pembayaran) IN ('cancelled','canceled');
UPDATE penjualan SET status_pembayaran = 'Cancelled (Dibatalkan)' WHERE LOWER(status_pembayaran) = 'refunded';
UPDATE penjualan SET status_pembayaran = 'TOP (Term Off Payment)' WHERE UPPER(status_pembayaran) = 'TOP';

-- Constrain to new ENUM
ALTER TABLE penjualan MODIFY COLUMN status_pembayaran ENUM(
  'Paid (Lunas)',
  'Pending (Menunggu)',
  'TOP (Term Off Payment)',
  'Cancelled (Dibatalkan)'
) NOT NULL DEFAULT 'Pending (Menunggu)';

COMMIT;

-- Rollback (manual):
-- ALTER TABLE penjualan MODIFY COLUMN metode_pembayaran ENUM('cash','transfer','credit_card','debit_card','e_wallet') NOT NULL;
-- ALTER TABLE penjualan MODIFY COLUMN status_pembayaran ENUM('pending','paid','cancelled','refunded') NOT NULL;
