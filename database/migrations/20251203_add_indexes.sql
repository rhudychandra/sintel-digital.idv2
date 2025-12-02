-- Add helpful indexes for SA/VF approval pairing and stock pivots
-- Safe to run multiple times; uses IF NOT EXISTS where supported by MariaDB 10.4/MySQL 8

-- Inventory: speed pairing + filtering
ALTER TABLE `inventory`
  ADD INDEX `idx_inventory_referensi` (`referensi`),
  ADD INDEX `idx_inventory_cabang_tipe_status` (`cabang_id`, `tipe_transaksi`, `status_approval`),
  ADD INDEX `idx_inventory_tanggal` (`tanggal`);

-- Text prefix index for keterangan (Ref: PSAVF-...) to accelerate LIKE searches
ALTER TABLE `inventory`
  ADD INDEX `idx_inventory_keterangan_prefix` (`keterangan`(100));

-- Produk: speed category-based mapping and pivots
ALTER TABLE `produk`
  ADD INDEX `idx_produk_kategori` (`kategori`),
  ADD INDEX `idx_produk_status` (`status`);

-- Pengajuan: common lookups
ALTER TABLE `pengajuan_stock`
  ADD INDEX `idx_pengajuan_warehouse_tanggal` (`warehouse_id`, `tanggal`),
  ADD INDEX `idx_pengajuan_outlet_tanggal` (`outlet_id`, `tanggal`);

ALTER TABLE `pengajuan_stock_items`
  ADD INDEX `idx_pengajuan_items_pengajuan` (`pengajuan_id`),
  ADD INDEX `idx_pengajuan_items_produk` (`produk_id`);

-- Outlet: speed RS lookups
ALTER TABLE `outlet`
  ADD INDEX `idx_outlet_nomor_rs` (`nomor_rs`),
  ADD INDEX `idx_outlet_jenis_rs` (`jenis_rs`);
