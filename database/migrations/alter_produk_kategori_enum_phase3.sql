-- Phase 3: Finalize produk.kategori ENUM to only the new categories
-- IMPORTANT: Run only after all rows have been migrated off old values.
-- Pre-check (run these to confirm 0 rows remain with old values):
-- SELECT COUNT(*) AS sisa_lama FROM produk WHERE kategori IN ('internet','tv_cable','phone','paket_bundling','enterprise');

USE sinar_telkom_dashboard;

-- Apply final ENUM set (keeps ONLY the new categories)
ALTER TABLE produk MODIFY COLUMN kategori ENUM(
  'Voucher Internet Lite',
  'Voucher Internet ByU',
  'Perdana Internet Lite',
  'Perdana Internet ByU',
  'LinkAja',
  'Finpay',
  'Voucher Segel Lite',
  'Voucher Segel ByU',
  'Perdana Segel Red 0K',
  'Perdana Segel ByU 0K',
  'Perdana Internet Special'
) NOT NULL;

-- Verify
SHOW COLUMNS FROM produk LIKE 'kategori';
SELECT DISTINCT kategori FROM produk ORDER BY kategori;