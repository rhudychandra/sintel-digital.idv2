-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 08:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sinar_telkom_dashboard`
--

-- --------------------------------------------------------

--
-- Table structure for table `cabang`
--

CREATE TABLE `cabang` (
  `cabang_id` int(11) NOT NULL,
  `kode_cabang` varchar(20) NOT NULL,
  `nama_cabang` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `provinsi` varchar(50) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cabang`
--

INSERT INTO `cabang` (`cabang_id`, `kode_cabang`, `nama_cabang`, `alamat`, `kota`, `provinsi`, `telepon`, `email`, `manager_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PGA', 'TAP Kota Pagar Alam', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '08', 'pagaralam@sinartelkom.com', 'Hari Priyanto', 'active', '2025-11-07 21:33:12', '2025-11-08 05:10:19'),
(2, 'LHT', 'TAP Lahat', 'Lahat', 'Lahat', 'Sumatera Selatan', '08', 'lahat@sinartelkom.com', 'Andrian Guntur', 'active', '2025-11-07 21:33:12', '2025-11-08 05:09:40'),
(4, 'MRS', 'TAP Musi Rawas', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '08', 'musirawas@sinartelkom.com', 'Perli', 'active', '2025-11-07 21:33:12', '2025-11-08 05:48:20'),
(5, 'LLG', 'TAP Kota Lubuklinggau', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '08', 'taplubuklinggau@sinartelkom.com', 'Wahyu Hidayat', 'active', '2025-11-07 21:33:12', '2025-11-08 05:07:55'),
(17, 'EMPL', 'TAP Empat Lawang (Tebing Tinggi)', 'Tebing Tinggi Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '08', 'empatlawangtbg@sinartelkom.com', 'Doddy Delta', 'active', '2025-11-08 05:11:27', '2025-11-08 05:11:27'),
(18, 'PDP', 'TAP Empat Lawang (Pendopo)', 'Pendopo Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '08', 'empatlawangpdp@sinartelkom.com', 'Jeni Handika', 'active', '2025-11-08 05:12:08', '2025-11-09 11:50:55'),
(44, 'WRHLLG', 'Warehouse Lubuklinggau', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '08', 'warehousllg@sinartelkom.com', 'Irfani', 'active', '2025-11-08 10:10:02', '2025-11-08 10:10:02'),
(45, 'WRHLHT', 'Warehouse Lahat', 'Lahat', 'Lahat', 'Sumatera Selatan', '08', 'warehouslht@sinartelkom.com', 'Irfani', 'active', '2025-11-08 10:10:39', '2025-11-08 10:10:39'),
(46, 'OUTER', 'Out Cluster', 'Out Cluster New Musi Rawas', 'Semua Kota', 'Semua Provinsi', '08', 'Outer@sinartelkom.com', 'Wirahadi Koesuma', 'active', '2025-11-09 11:33:23', '2025-11-09 11:33:23'),
(47, 'INNER', 'Player Inner', 'Seluruh City Cluster New Musi Rawas', 'Seluruh City Cluster New Musi Rawas', 'Sumatera Selatan', '08', 'playerinner@sinartelkom.com', 'Wirahadi Koesuma', 'active', '2025-11-09 11:34:29', '2025-11-09 11:34:29'),
(48, 'MRSU', 'TAP Musi Rawas Utara', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '08', 'musirawasutara@sinartelkom.com', 'Ahmad Rifko', 'active', '2025-11-09 11:52:02', '2025-11-09 11:52:02');

-- --------------------------------------------------------

--
-- Table structure for table `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `detail_id` int(11) NOT NULL,
  `penjualan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `diskon` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_penjualan`
--

INSERT INTO `detail_penjualan` (`detail_id`, `penjualan_id`, `produk_id`, `nama_produk`, `harga_satuan`, `jumlah`, `diskon`, `subtotal`, `created_at`) VALUES
(6, 6, 34, 'Voucher Fisik Segel Lite', 500.00, 3000, 0.00, 1500000.00, '2025-11-12 06:44:09'),
(7, 7, 34, 'Voucher Fisik Segel Lite', 500.00, 1000, 0.00, 500000.00, '2025-11-12 06:44:45'),
(8, 8, 34, 'Voucher Fisik Segel Lite', 500.00, 150, 0.00, 75000.00, '2025-11-12 06:49:58'),
(9, 9, 34, 'Voucher Fisik Segel Lite', 500.00, 200, 0.00, 100000.00, '2025-11-12 06:56:42'),
(10, 10, 26, 'Preload 3GB 30 Hari', 28000.00, 12, 0.00, 336000.00, '2025-11-12 10:31:47'),
(11, 10, 34, 'Voucher Fisik Segel Lite', 500.00, 500, 0.00, 250000.00, '2025-11-12 10:31:47'),
(12, 11, 34, 'Voucher Fisik Segel Lite', 500.00, 2000, 0.00, 1000000.00, '2025-11-13 17:33:37'),
(13, 12, 34, 'Voucher Fisik Segel Lite', 500.00, 700, 0.00, 350000.00, '2025-11-13 18:16:34'),
(14, 13, 34, 'Voucher Fisik Segel Lite', 500.00, 23445, 0.00, 11722500.00, '2025-11-13 20:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `tipe_transaksi` enum('masuk','keluar','adjustment','return') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `stok_sebelum` int(11) NOT NULL,
  `stok_sesudah` int(11) NOT NULL,
  `referensi` varchar(100) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `produk_id`, `cabang_id`, `tanggal`, `tipe_transaksi`, `jumlah`, `stok_sebelum`, `stok_sesudah`, `referensi`, `keterangan`, `status_approval`, `user_id`, `created_at`) VALUES
(6, 34, 44, '2025-11-12', 'masuk', 275000, 0, 275000, 'MASUK-20251112-3681', 'Stock Masuk - Alasan: DO Cluster', 'approved', 9, '2025-11-12 06:42:28'),
(7, 34, 45, '2025-11-12', 'masuk', 275000, 0, 275000, 'MASUK-20251112-4144', 'Stock Masuk - Alasan: DO Cluster', 'approved', 9, '2025-11-12 06:42:38'),
(8, 34, 45, '2025-11-12', 'keluar', 3000, 550000, 547000, 'INV-20251112-2905', 'Penjualan ke reseller: Irfani', 'approved', 9, '2025-11-12 06:44:09'),
(9, 34, 44, '2025-11-12', 'keluar', 1000, 547000, 546000, 'INV-20251112-1145', 'Penjualan ke reseller: Wirahadi Koesuma', 'approved', 9, '2025-11-12 06:44:45'),
(10, 34, 45, '2025-11-12', 'keluar', 150, 546000, 545850, 'INV-20251112-9174', 'Penjualan ke reseller: Irfani', 'approved', 8, '2025-11-12 06:49:58'),
(11, 34, 45, '2025-11-12', 'keluar', 200, 545850, 545650, 'INV-20251112-9136', 'Penjualan ke reseller: Irfani', 'approved', 8, '2025-11-12 06:56:42'),
(12, 26, 45, '2025-11-12', 'masuk', 2000, 0, 2000, 'MASUK-20251112-3526', 'Stock Masuk - Alasan: DO Cluster', 'approved', 8, '2025-11-12 10:31:24'),
(13, 26, 45, '2025-11-12', 'keluar', 12, 2000, 1988, 'INV-20251112-8401', 'Penjualan ke reseller: Irfani', 'approved', 8, '2025-11-12 10:31:47'),
(14, 34, 45, '2025-11-12', 'keluar', 500, 545650, 545150, 'INV-20251112-8401', 'Penjualan ke reseller: Irfani', 'approved', 8, '2025-11-12 10:31:47'),
(15, 34, 44, '2025-11-13', 'keluar', 2000, 545150, 543150, 'INV-20251113-5912', 'Penjualan ke reseller: Irfani', 'approved', 9, '2025-11-13 17:33:37'),
(16, 34, 45, '2025-11-13', 'keluar', 700, 543150, 542450, 'INV-20251113-6997', 'Penjualan ke reseller: Irfani', 'approved', 8, '2025-11-13 18:16:34'),
(17, 34, 45, '2025-11-13', 'keluar', 23445, 542450, 519005, 'INV-20251113-3702', 'Penjualan ke reseller: Wirahadi Koesuma', 'approved', 8, '2025-11-13 20:20:56'),
(18, 14, 44, '2025-12-03', 'keluar', 2000, 0, -2000, 'PSAVF-8', 'Pengajuan SA/VF: pengurangan stok gudang', 'pending', 9, '2025-12-02 18:38:17'),
(19, 34, 44, '2025-12-03', 'keluar', 1000, 272000, 271000, 'PSAVF-9', 'Pengajuan SA/VF: pengurangan stok gudang', 'pending', 9, '2025-12-02 18:44:36'),
(20, 34, 44, '2025-12-02', 'keluar', 1000, 271000, 270000, 'PSAVF-10', 'Pengajuan SA/VF: pengurangan stok gudang', 'pending', 9, '2025-12-02 18:57:03'),
(21, 34, 44, '2025-12-02', 'keluar', 1000, 270000, 269000, 'PSAVF-11', 'Pengajuan SA/VF: pengurangan stok gudang', 'approved', 9, '2025-12-02 19:02:14'),
(22, 34, 44, '2025-12-02', 'keluar', 500, 269000, 269000, 'PSAVF-12', 'Pengajuan SA/VF: pengurangan stok gudang | Tujuan: Player Inner | RS: Counter Cell Sejahtera (RS-003)', 'pending', 9, '2025-12-02 19:10:20'),
(23, 16, 47, '2025-12-02', 'masuk', 500, 0, 500, 'PSAVF-12', 'Stock Masuk - Pengajuan SA/VF dari Warehouse Lubuklinggau | RS: Counter Cell Sejahtera (RS-003)', 'approved', 9, '2025-12-02 19:10:20'),
(24, 34, 44, '2025-12-03', 'keluar', 1000, 268500, 268500, 'PSAVF-13', 'Pengajuan SA/VF: pengurangan stok gudang | Tujuan: Player Inner | RS: Warung Maju Makmur (RS-002)', 'pending', 9, '2025-12-02 19:10:42'),
(25, 14, 47, '2025-12-03', 'masuk', 1000, 0, 1000, 'PSAVF-13', 'Stock Masuk - Pengajuan SA/VF dari Warehouse Lubuklinggau | RS: Warung Maju Makmur (RS-002)', 'approved', 9, '2025-12-02 19:10:42'),
(26, 34, 44, '2025-12-02', 'keluar', 1000, 267500, 267500, 'PSAVF-14', 'Pengajuan SA/VF: pengurangan stok gudang | Tujuan: Player Inner | RS: Toko Sinar Jaya (RS-001)', 'pending', 9, '2025-12-02 19:11:45'),
(27, 17, 47, '2025-12-02', 'masuk', 1000, 0, 1000, 'PSAVF-14', 'Stock Masuk - Pengajuan SA/VF dari Warehouse Lubuklinggau | RS: Toko Sinar Jaya (RS-001)', 'approved', 9, '2025-12-02 19:11:45'),
(28, 34, 44, '2025-12-04', 'keluar', 1000, 266500, 265500, 'PSAVF-15', 'Pengajuan SA/VF: pengurangan stok gudang | Tujuan: Player Inner | RS: Toko Sinar Jaya (RS-001) | Ref: PSAVF-15', 'approved', 9, '2025-12-02 19:16:12'),
(29, 14, 47, '2025-12-04', 'masuk', 1000, 1000, 2000, 'PSAVF-15', 'Stock Masuk - Pengajuan SA/VF dari Warehouse Lubuklinggau | RS: Toko Sinar Jaya (RS-001) | Ref: PSAVF-15', 'approved', 9, '2025-12-02 19:16:12'),
(30, 34, 44, '2025-12-02', 'keluar', 2755, 265500, 262745, 'PSAVF-16', 'Pengajuan SA/VF: pengurangan stok gudang | Tujuan: Player Inner | RS: Toko Sinar Jaya (RS-001) | Ref: PSAVF-16', 'approved', 9, '2025-12-02 19:17:45'),
(31, 13, 47, '2025-12-02', 'masuk', 2755, 0, 2755, 'PSAVF-16', 'Stock Masuk - Pengajuan SA/VF dari Warehouse Lubuklinggau | RS: Toko Sinar Jaya (RS-001) | Ref: PSAVF-16', 'approved', 9, '2025-12-02 19:17:45');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_produk`
--

CREATE TABLE `kategori_produk` (
  `kategori_id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT '?',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_produk`
--

INSERT INTO `kategori_produk` (`kategori_id`, `nama_kategori`, `deskripsi`, `icon`, `status`, `created_at`, `updated_at`) VALUES
(6, 'Voucher Internet Lite', 'Voucher Fisik Internet Lite', '🔖', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(7, 'Voucher Internet ByU', 'Voucher Internet ByU', '🩹', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(8, 'Perdana Internet Lite', 'Perdana Internet Lite', '📕', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(9, 'Perdana Internet ByU', 'Perdana Internet ByU', '📘', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(10, 'LinkAja', 'Saldo LinkAja', '💴', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(11, 'Finpay', 'Saldo Finpay', '💶', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(12, 'Voucher Segel Lite', 'Voucher Segel Lite', '♦️', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(13, 'Voucher Segel ByU', 'Voucher Segel ByU', '♠️', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(14, 'Perdana Segel Red 0K', 'Perdana Segel Red 0K', '📕', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(15, 'Perdana Segel ByU 0K', 'Perdana Segel ByU 0K', '📘', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31'),
(16, 'Perdana Internet Special', 'Perdana Internet Special (Nomor Cantik 260GB)', '💎', 'active', '2025-11-12 06:25:31', '2025-11-12 06:25:31');

-- --------------------------------------------------------

--
-- Table structure for table `outlet`
--

CREATE TABLE `outlet` (
  `outlet_id` int(11) NOT NULL,
  `nama_outlet` varchar(255) NOT NULL COMMENT 'Nama toko/outlet',
  `nomor_rs` varchar(50) NOT NULL COMMENT 'Nomor registrasi outlet',
  `id_digipos` varchar(100) DEFAULT NULL COMMENT 'ID dari sistem Digipos',
  `nik_ktp` varchar(20) DEFAULT NULL COMMENT 'NIK KTP pemilik (boleh kosong)',
  `kelurahan_desa` varchar(100) DEFAULT NULL COMMENT 'Kelurahan atau Desa',
  `kecamatan` varchar(100) DEFAULT NULL COMMENT 'Kecamatan',
  `city` varchar(100) DEFAULT NULL COMMENT 'Kota/Kabupaten',
  `nama_pemilik` varchar(255) NOT NULL COMMENT 'Nama pemilik outlet',
  `nomor_hp_pemilik` varchar(20) NOT NULL COMMENT 'Nomor HP pemilik',
  `type_outlet` varchar(100) DEFAULT NULL COMMENT 'Tipe outlet (Retail, Grosir, dll)',
  `jadwal_kategori` varchar(50) DEFAULT NULL COMMENT 'Kategori jadwal kunjungan',
  `hari` varchar(50) DEFAULT NULL COMMENT 'Hari kunjungan',
  `sales_force_id` int(11) DEFAULT NULL COMMENT 'ID sales force dari tabel reseller',
  `cabang_id` int(11) DEFAULT NULL COMMENT 'ID cabang',
  `status_outlet` enum('PJP','Non PJP') DEFAULT 'Non PJP' COMMENT 'Status PJP (Productive Journey Plan)',
  `jenis_rs` enum('Retail','Pareto','RS Eksekusi Voucher','RS Eksekusi SA') DEFAULT 'Retail' COMMENT 'Jenis klasifikasi outlet',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `outlet`
--

INSERT INTO `outlet` (`outlet_id`, `nama_outlet`, `nomor_rs`, `id_digipos`, `nik_ktp`, `kelurahan_desa`, `kecamatan`, `city`, `nama_pemilik`, `nomor_hp_pemilik`, `type_outlet`, `jadwal_kategori`, `hari`, `sales_force_id`, `cabang_id`, `status_outlet`, `jenis_rs`, `created_at`, `updated_at`) VALUES
(1, 'Toko Sinar Jaya', 'RS-001', 'DGP-001', '3201012345678901', 'Sukajadi', 'Bandung Wetan', 'Bandung', 'Budi Santoso', '081234567890', '', '', 'Senin', NULL, NULL, 'PJP', 'RS Eksekusi SA', '2025-11-13 16:24:46', '2025-11-15 15:11:47'),
(2, 'Warung Maju Makmur', 'RS-002', 'DGP-002', NULL, 'Cicaheum', 'Kiaracondong', 'Bandung', 'Siti Aminah', '082345678901', '', '', 'Selasa', NULL, NULL, 'PJP', 'RS Eksekusi Voucher', '2025-11-13 16:24:46', '2025-11-15 15:11:41'),
(3, 'Counter Cell Sejahtera', 'RS-003', 'DGP-003', '3201023456789012', 'Antapani', 'Antapani', 'Bandung', 'Agus Wijaya', '083456789012', 'Retail', 'Kategori A', 'Rabu', NULL, NULL, 'Non PJP', 'RS Eksekusi Voucher', '2025-11-13 16:24:46', '2025-11-13 16:24:46'),
(4, 'Dandi Cell', '081378999785', '13001002', '18901890', 'F Trikoyo', 'F Trikoyo', 'Musi Rawas', 'Dandi', '08130813', 'Konter Pulsa', 'F4 - Kunjungan 1 Minggu sekali', 'Senin', 38, 4, 'PJP', 'RS Eksekusi Voucher', '2025-11-15 17:21:19', '2025-11-15 17:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `pelanggan_id` int(11) NOT NULL,
  `kode_pelanggan` varchar(50) NOT NULL,
  `nama_pelanggan` varchar(200) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `tipe_pelanggan` enum('individual','corporate') DEFAULT 'individual',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`pelanggan_id`, `kode_pelanggan`, `nama_pelanggan`, `email`, `phone`, `alamat`, `kota`, `provinsi`, `kode_pos`, `tipe_pelanggan`, `status`, `created_at`, `updated_at`) VALUES
(6, 'CUST-34196', 'Irfani', NULL, '000000000', NULL, NULL, NULL, NULL, 'corporate', 'active', '2025-11-12 06:44:09', '2025-11-12 06:44:09'),
(7, 'CUST-16335', 'Wirahadi Koesuma', NULL, '000000000', NULL, NULL, NULL, NULL, 'corporate', 'active', '2025-11-12 06:44:45', '2025-11-12 06:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_stock`
--

CREATE TABLE `pengajuan_stock` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `rs_type` enum('sa','vf') NOT NULL DEFAULT 'sa',
  `outlet_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `jenis` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `total_saldo` decimal(16,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_stock`
--

INSERT INTO `pengajuan_stock` (`id`, `tanggal`, `rs_type`, `outlet_id`, `requester_id`, `jenis`, `warehouse_id`, `total_qty`, `total_saldo`, `created_by`, `created_at`) VALUES
(1, '2025-11-15', 'sa', 1, 35, '0', 44, 12, 180000.00, 15, '2025-11-16 03:31:58'),
(2, '2025-11-15', 'vf', 1, 35, '0', 44, 445, 2755246.00, 15, '2025-11-16 04:09:42'),
(3, '2025-11-15', 'sa', 4, 36, '0', 45, 3896, 109088000.00, 15, '2025-11-16 04:10:22'),
(4, '2025-11-15', 'vf', 3, 2, '0', 44, 2990, 31361668.00, 15, '2025-11-16 04:17:12'),
(5, '2025-11-15', 'vf', 1, 35, '0', 44, 888, 8031190.00, 15, '2025-11-16 04:17:12'),
(6, '2025-11-15', 'vf', 4, 7, '0', 45, 655, 5840266.00, 15, '2025-11-16 04:17:12'),
(7, '2025-12-02', 'vf', 2, 35, '0', 44, 5500, 44443200.00, 9, '2025-12-03 00:08:13'),
(8, '2025-12-03', 'vf', 1, 35, '0', 44, 2000, 8800000.00, 9, '2025-12-03 01:38:17'),
(9, '2025-12-03', 'vf', 4, 35, '0', 44, 1000, 17040000.00, 9, '2025-12-03 01:44:36'),
(10, '2025-12-02', 'vf', 1, 35, '0', 44, 1000, 10016000.00, 9, '2025-12-03 01:57:03'),
(11, '2025-12-02', 'vf', 1, 35, '0', 44, 1000, 6401600.00, 9, '2025-12-03 02:02:14'),
(12, '2025-12-02', 'vf', 3, 35, '0', 44, 500, 4280000.00, 9, '2025-12-03 02:10:20'),
(13, '2025-12-03', 'vf', 2, 35, '0', 44, 1000, 4400000.00, 9, '2025-12-03 02:10:42'),
(14, '2025-12-02', 'vf', 1, 35, '0', 44, 1000, 10016000.00, 9, '2025-12-03 02:11:45'),
(15, '2025-12-04', 'vf', 1, 35, '0', 44, 1000, 4400000.00, 9, '2025-12-03 02:16:12'),
(16, '2025-12-02', 'vf', 1, 35, '0', 44, 2755, 24905200.00, 9, '2025-12-03 02:17:45');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_stock_items`
--

CREATE TABLE `pengajuan_stock_items` (
  `id` int(11) NOT NULL,
  `pengajuan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga` decimal(16,2) NOT NULL,
  `nominal` decimal(16,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_stock_items`
--

INSERT INTO `pengajuan_stock_items` (`id`, `pengajuan_id`, `produk_id`, `qty`, `harga`, `nominal`) VALUES
(1, 1, 29, 12, 15000.00, 180000.00),
(2, 2, 11, 123, 8002.00, 984246.00),
(3, 2, 14, 322, 5500.00, 1771000.00),
(4, 3, 27, 2320, 28000.00, 64960000.00),
(5, 3, 28, 1576, 28000.00, 44128000.00),
(6, 4, 16, 2756, 10700.00, 29489200.00),
(7, 4, 11, 234, 8002.00, 1872468.00),
(8, 5, 16, 343, 10700.00, 3670100.00),
(9, 5, 11, 545, 8002.00, 4361090.00),
(10, 6, 16, 222, 10700.00, 2375400.00),
(11, 6, 11, 433, 8002.00, 3464866.00),
(12, 7, 11, 2000, 6401.60, 12803200.00),
(13, 7, 15, 3000, 9120.00, 27360000.00),
(14, 7, 16, 500, 8560.00, 4280000.00),
(15, 8, 14, 2000, 4400.00, 8800000.00),
(16, 9, 18, 1000, 17040.00, 17040000.00),
(17, 10, 17, 1000, 10016.00, 10016000.00),
(18, 11, 11, 1000, 6401.60, 6401600.00),
(19, 12, 16, 500, 8560.00, 4280000.00),
(20, 13, 14, 1000, 4400.00, 4400000.00),
(21, 14, 17, 1000, 10016.00, 10016000.00),
(22, 15, 14, 1000, 4400.00, 4400000.00),
(23, 16, 13, 2755, 9040.00, 24905200.00);

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `penjualan_id` int(11) NOT NULL,
  `no_invoice` varchar(50) NOT NULL,
  `tanggal_penjualan` date NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  `reseller_id` int(11) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `diskon` decimal(15,2) DEFAULT 0.00,
  `pajak` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `metode_pembayaran` enum('Transfer','Cash','Budget Komitmen','Finpay') NOT NULL DEFAULT 'Transfer',
  `status_pembayaran` enum('Paid (Lunas)','Pending (Menunggu)','TOP (Term Off Payment)','Cancelled (Dibatalkan)') NOT NULL DEFAULT 'Pending (Menunggu)',
  `status_pengiriman` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penjualan`
--

INSERT INTO `penjualan` (`penjualan_id`, `no_invoice`, `tanggal_penjualan`, `pelanggan_id`, `user_id`, `cabang_id`, `reseller_id`, `subtotal`, `diskon`, `pajak`, `total`, `metode_pembayaran`, `status_pembayaran`, `status_pengiriman`, `catatan`, `created_at`, `updated_at`) VALUES
(6, 'INV-20251112-2905', '2025-11-12', 6, 9, 45, 39, 1500000.00, 0.00, 0.00, 1500000.00, 'Transfer', 'Paid (Lunas)', 'pending', NULL, '2025-11-12 06:44:09', '2025-11-12 06:44:09'),
(7, 'INV-20251112-1145', '2025-11-12', 7, 9, 44, 37, 500000.00, 0.00, 0.00, 500000.00, 'Transfer', 'Paid (Lunas)', 'pending', NULL, '2025-11-12 06:44:45', '2025-11-12 06:44:45'),
(8, 'INV-20251112-9174', '2025-11-12', 6, 8, 45, 39, 75000.00, 0.00, 0.00, 75000.00, 'Budget Komitmen', 'TOP (Term Off Payment)', 'pending', NULL, '2025-11-12 06:49:58', '2025-11-12 06:49:58'),
(9, 'INV-20251112-9136', '2025-11-12', 6, 8, 45, 39, 100000.00, 0.00, 0.00, 100000.00, 'Transfer', 'Paid (Lunas)', 'pending', NULL, '2025-11-12 06:56:42', '2025-11-12 06:56:42'),
(10, 'INV-20251112-8401', '2025-11-12', 6, 8, 45, 39, 586000.00, 0.00, 0.00, 586000.00, 'Cash', 'Paid (Lunas)', 'pending', NULL, '2025-11-12 10:31:47', '2025-11-12 10:31:47'),
(11, 'INV-20251113-5912', '2025-11-13', 6, 9, 44, 38, 1000000.00, 0.00, 0.00, 1000000.00, 'Transfer', 'Paid (Lunas)', 'pending', NULL, '2025-11-13 17:33:37', '2025-11-13 17:33:37'),
(12, 'INV-20251113-6997', '2025-11-13', 6, 8, 45, 39, 350000.00, 0.00, 0.00, 350000.00, 'Transfer', 'Paid (Lunas)', 'pending', NULL, '2025-11-13 18:16:34', '2025-11-13 18:16:34'),
(13, 'INV-20251113-3702', '2025-11-13', 7, 8, 45, 36, 11722500.00, 0.00, 0.00, 11722500.00, 'Transfer', 'Paid (Lunas)', 'pending', NULL, '2025-11-13 20:20:56', '2025-11-13 20:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `penjualan_outlet`
--

CREATE TABLE `penjualan_outlet` (
  `penjualan_outlet_id` int(11) NOT NULL,
  `tanggal` date NOT NULL COMMENT 'Tanggal penjualan',
  `sales_force_id` int(11) NOT NULL COMMENT 'ID sales force dari tabel reseller',
  `produk_id` int(11) NOT NULL COMMENT 'ID produk',
  `outlet_id` int(11) NOT NULL COMMENT 'ID outlet',
  `qty` int(11) NOT NULL COMMENT 'Jumlah produk terjual',
  `nominal` decimal(15,2) NOT NULL COMMENT 'Total nominal penjualan',
  `keterangan` text DEFAULT NULL COMMENT 'Keterangan tambahan',
  `cabang_id` int(11) DEFAULT NULL COMMENT 'ID cabang',
  `created_by` int(11) DEFAULT NULL COMMENT 'User yang input',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penjualan_outlet`
--

INSERT INTO `penjualan_outlet` (`penjualan_outlet_id`, `tanggal`, `sales_force_id`, `produk_id`, `outlet_id`, `qty`, `nominal`, `keterangan`, `cabang_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2025-11-13', 38, 34, 1, 250, 125000.00, '', 44, 9, '2025-11-13 17:46:31', '2025-11-13 17:46:31'),
(2, '2025-11-13', 38, 34, 2, 1750, 875000.00, '', 44, 9, '2025-11-13 17:46:31', '2025-11-13 17:46:31'),
(3, '2025-11-12', 37, 34, 2, 200, 100000.00, '', NULL, 8, '2025-11-13 17:57:46', '2025-11-13 17:57:46'),
(4, '2025-11-12', 37, 34, 1, 800, 400000.00, '', NULL, 8, '2025-11-13 17:57:46', '2025-11-13 17:57:46'),
(5, '2025-11-12', 39, 26, 1, 12, 336000.00, '', NULL, 8, '2025-11-13 18:01:09', '2025-11-13 18:01:09'),
(6, '2025-11-12', 39, 34, 1, 1850, 925000.00, '', NULL, 8, '2025-11-13 18:01:09', '2025-11-13 18:01:09'),
(7, '2025-11-12', 39, 34, 2, 2000, 1000000.00, '', NULL, 8, '2025-11-13 18:01:09', '2025-11-13 18:01:09'),
(8, '2025-11-13', 39, 34, 3, 700, 350000.00, '', NULL, 8, '2025-11-13 18:17:06', '2025-11-13 18:17:06'),
(9, '2025-11-13', 39, 34, 3, 700, 350000.00, '', NULL, 8, '2025-11-13 18:17:27', '2025-11-13 18:17:27'),
(10, '2025-11-13', 39, 34, 3, 700, 350000.00, '', NULL, 8, '2025-11-13 18:26:16', '2025-11-13 18:26:16'),
(11, '2025-11-13', 39, 34, 2, 700, 350000.00, '', NULL, 8, '2025-11-13 18:27:51', '2025-11-13 18:27:51'),
(12, '2025-11-13', 39, 34, 2, 700, 350000.00, '', NULL, 8, '2025-11-13 18:33:05', '2025-11-13 18:33:05'),
(13, '2025-11-13', 39, 34, 3, 700, 350000.00, '', 45, 9, '2025-11-13 19:49:50', '2025-11-13 19:49:50'),
(14, '2025-11-13', 39, 34, 3, 700, 350000.00, '', NULL, 8, '2025-11-13 20:12:45', '2025-11-13 20:12:45'),
(15, '2025-11-13', 36, 34, 3, 13445, 6722500.00, '', NULL, 8, '2025-11-13 20:22:07', '2025-11-13 20:22:07'),
(16, '2025-11-13', 36, 34, 1, 10000, 5000000.00, '', NULL, 8, '2025-11-13 20:22:07', '2025-11-13 20:22:07'),
(17, '2025-11-13', 36, 34, 2, 23445, 11722500.00, '', NULL, 8, '2025-11-13 20:28:25', '2025-11-13 20:28:25'),
(18, '2025-11-13', 38, 34, 3, 2000, 1000000.00, '', 44, 9, '2025-11-13 20:45:37', '2025-11-13 20:45:37');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `produk_id` int(11) NOT NULL,
  `kode_produk` varchar(50) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(15,2) NOT NULL,
  `hpp_saldo` decimal(15,2) DEFAULT 0.00 COMMENT 'HPP untuk produk saldo/virtual',
  `hpp_fisik` decimal(15,2) DEFAULT 0.00 COMMENT 'HPP untuk produk fisik',
  `profit_margin_saldo` decimal(5,2) DEFAULT 0.00 COMMENT 'Margin saldo: ((harga-hpp_saldo)/hpp_saldo)*100',
  `profit_margin_fisik` decimal(5,2) DEFAULT 0.00 COMMENT 'Margin fisik: ((harga-hpp_fisik)/hpp_fisik)*100',
  `harga_promo` decimal(15,2) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `satuan` varchar(20) DEFAULT 'unit',
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`produk_id`, `kode_produk`, `nama_produk`, `kategori`, `kategori_id`, `cabang_id`, `deskripsi`, `harga`, `hpp_saldo`, `hpp_fisik`, `profit_margin_saldo`, `profit_margin_fisik`, `harga_promo`, `stok`, `satuan`, `status`, `created_at`, `updated_at`) VALUES
(11, 'VF-Lite-Inet -001', 'VF Internet Lite 1,5GB 3Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 1,5GB 3Hari', 8002.00, 6401.60, 6801.70, 25.00, 17.65, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 15:59:49'),
(12, 'VF-Lite-Inet -002', 'VF Internet Lite 2GB 3Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 2GB 3Hari', 10120.00, 8096.00, 8602.00, 25.00, 17.65, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 15:59:49'),
(13, 'VF-Lite-Inet -003', 'VF Internet Lite 3GB 3Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 3GB 3Hari', 11300.00, 9040.00, 9605.00, 25.00, 17.65, NULL, 2755, 'unit', 'active', '2025-11-12 06:37:31', '2025-12-02 19:18:04'),
(14, 'VF-Lite-Inet -004', 'VF Internet Lite 1GB 1Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 1GB 1Hari', 5500.00, 4400.00, 4675.00, 25.00, 17.65, NULL, 2000, 'unit', 'active', '2025-11-12 06:37:31', '2025-12-02 19:16:45'),
(15, 'VF-Lite-Inet -005', 'VF Internet Lite 2GB 5Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 2GB 5Hari', 11400.00, 9120.00, 9690.00, 25.00, 17.65, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 15:59:49'),
(16, 'VF-Lite-Inet -006', 'VF Internet Lite 2,5GB 5Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 2,5GB 5Hari', 10700.00, 8560.00, 9095.00, 25.00, 17.65, NULL, 500, 'unit', 'active', '2025-11-12 06:37:31', '2025-12-02 19:12:53'),
(17, 'VF-Lite-Inet -007', 'VF Internet Lite 3GB 5Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 3GB 5Hari', 12520.00, 10016.00, 10642.00, 25.00, 17.65, NULL, 1000, 'unit', 'active', '2025-11-12 06:37:31', '2025-12-02 19:12:52'),
(18, 'VF-Lite-Inet -008', 'VF Internet Lite 5GB 5Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 5GB 5Hari', 21300.00, 17040.00, 18105.00, 25.00, 17.65, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 15:59:49'),
(19, 'VF-Lite-Inet -009', 'VF Internet Lite 7GB 7Hari', 'Voucher Internet Lite', NULL, NULL, 'VF Internet Lite 7GB 7Hari', 25500.00, 20400.00, 21675.00, 25.00, 17.65, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 15:59:49'),
(20, 'VF-Lite-Inet -010', 'VF Internet ByU ByU 1GB 1Hari', 'Voucher Internet ByU', NULL, NULL, 'VF Internet ByU ByU 1GB 1Hari', 3500.00, 2800.00, 2975.00, 25.00, 17.65, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 15:59:49'),
(21, 'VF-Lite-Inet -011', 'VF Internet ByU ByU 2GB 1Hari', 'Voucher Internet ByU', NULL, NULL, 'VF Internet ByU ByU 2GB 1Hari', 6000.00, 4800.00, 5100.00, 25.00, 17.65, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 15:59:49'),
(22, 'VF-ByU-Inet -001', 'VF Internet ByU ByU 3GB 3Hari', 'Voucher Internet ByU', NULL, NULL, 'VF Internet ByU ByU 3GB 3Hari', 8500.00, 6800.00, 7225.00, 25.00, 17.65, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 15:59:49'),
(23, 'PRD-1763050187-947', 'VF Internet ByU ByU 4GB 7Hari', 'Voucher Internet ByU', NULL, NULL, 'VF Internet ByU ByU 4GB 7Hari', 10500.00, 10000.00, 10.00, 490.00, 4.90, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:09:47'),
(24, 'PRD-1763050169-776', 'VF Internet ByU ByU 9GB 30Hari', 'Voucher Internet ByU', NULL, NULL, 'VF Internet ByU ByU 9GB 30Hari', 25500.00, 25000.00, 10.00, 490.00, 1.96, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:09:29'),
(25, 'PRD-1763050119-679', 'VF Internet ByU ByU 7GB 30Hari', 'Voucher Internet ByU', NULL, NULL, 'VF Internet ByU ByU 7GB 30Hari', 15500.00, 15000.00, 10.00, 490.00, 3.26, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:08:39'),
(26, 'PRD-1763050072-312', 'Preload 3GB 30 Hari', 'Perdana Internet Lite', NULL, NULL, 'Preload 3GB 30 Hari', 28000.00, 0.00, 26910.00, 999.99, 4.05, NULL, 1988, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:07:52'),
(27, 'PRD-1763049818-386', 'Perdana Internet SA 3GB', 'Perdana Internet Lite', NULL, NULL, 'Perdana Internet SA 3GB', 28000.00, 19900.00, 10000.00, -999.99, -6.35, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:03:38'),
(28, 'PRD-1763049798-358', 'SA ByU 3GB 30D', 'Perdana Internet ByU', NULL, NULL, 'SA ByU 3GB 30D', 28000.00, 19900.00, 10000.00, -999.99, -6.35, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:03:18'),
(29, 'PRD-1763049782-177', 'SA ByU 3GB 30D (Alladin)', 'Perdana Internet ByU', NULL, NULL, 'SA ByU 3GB 30D (Alladin)', 15000.00, 19900.00, 10000.00, -999.99, -49.83, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:03:02'),
(30, 'PRD-1763049765-442', 'Saldo LinkAja', 'LinkAja', NULL, NULL, 'Saldo LinkAja', 1.00, 1.00, 0.00, 0.00, 0.00, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:02:45'),
(31, 'PRD-1763049756-955', 'Saldo Finpay', 'Finpay', NULL, NULL, 'Saldo Finpay', 1.00, 1.00, 0.00, 0.00, 0.00, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:02:36'),
(32, 'PRD-1763049737-157', 'Perdana Segel Red 0K S261', 'Perdana Segel Red 0K', NULL, NULL, 'Perdana Segel Red 0K', 10000.00, 0.00, 10000.00, 0.00, 0.00, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:02:17'),
(33, 'PRD-1763049698-620', 'Perdana Segel ByU 0K', 'Perdana Segel ByU 0K', NULL, NULL, 'Perdana Segel ByU 0K', 10000.00, 0.00, 10000.00, 0.00, 0.00, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:01:38'),
(34, 'PRD-1763049653-295', 'Voucher Fisik Segel Lite', 'Voucher Segel Lite', NULL, NULL, 'Voucher Fisik Segel Lite', 500.00, 0.00, 266.00, 234.00, 87.97, NULL, 515250, 'unit', 'active', '2025-11-12 06:37:31', '2025-12-02 19:18:04'),
(35, 'PRD-1763049621-532', 'Voucher Fisik Segel ByU', 'Voucher Segel ByU', NULL, NULL, 'Voucher Fisik Segel ByU', 500.00, 0.00, 10.00, 490.00, 999.99, NULL, 0, 'unit', 'active', '2025-11-12 06:37:31', '2025-11-13 16:00:21');

-- --------------------------------------------------------

--
-- Table structure for table `reseller`
--

CREATE TABLE `reseller` (
  `reseller_id` int(11) NOT NULL,
  `kode_reseller` varchar(20) NOT NULL,
  `nama_reseller` varchar(100) NOT NULL,
  `nama_perusahaan` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `provinsi` varchar(50) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `total_penjualan` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `kategori` enum('Sales Force','General Manager','Manager','Supervisor','Player/Pemain','Merchant') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reseller`
--

INSERT INTO `reseller` (`reseller_id`, `kode_reseller`, `nama_reseller`, `nama_perusahaan`, `alamat`, `kota`, `provinsi`, `telepon`, `email`, `contact_person`, `cabang_id`, `status`, `total_penjualan`, `created_at`, `updated_at`, `kategori`) VALUES
(1, 'TPT005', 'Andrian Guntur', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Andrian Guntur', 2, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:19:03', 'Supervisor'),
(2, 'TPT006', 'Wahyu Hidayat', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Wahyu Hidayat', 5, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:18:58', 'Supervisor'),
(3, 'TPT007', 'Perli', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Perli', 4, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:18:51', 'Supervisor'),
(4, 'TPT008', 'Dody Delta', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Dody Delta', 17, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:18:46', 'Supervisor'),
(5, 'TPT009', 'Ahmad Rifko', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Ahmad Rifko', 48, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:18:39', 'Supervisor'),
(6, 'TPT010', 'Hari Priyanto', 'CV. SINAR TELEKOM', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Hari Priyanto', 1, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:18:30', 'Supervisor'),
(7, 'TPT011', 'Jeni Handika', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Jeni Handika', 18, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:18:22', 'Supervisor'),
(8, 'TPT012', 'Donal Septian', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Donal Septian', 18, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:18:13', 'Sales Force'),
(9, 'TPT013', 'Ego Saputra', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Ego Saputra', 18, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:18:06', 'Sales Force'),
(10, 'TPT014', 'Kgs Jauhari Fikri', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Kgs Jauhari Fikri', 17, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:57', 'Sales Force'),
(11, 'TPT015', 'Angga Ihza Rambe', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Angga Ihza Rambe', 5, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:50', 'Sales Force'),
(12, 'TPT016', 'Angga Triska', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Angga Triska', 5, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:39', 'Sales Force'),
(13, 'TPT017', 'Derry Setyadi', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Derry Setyadi', 5, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:34', 'Sales Force'),
(14, 'TPT018', 'Jaka Umbara', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Jaka Umbara', 5, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:29', 'Sales Force'),
(15, 'TPT019', 'Leo Waldi', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Leo Waldi', 5, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:24', 'Sales Force'),
(16, 'TPT020', 'Frediansyah', 'CV. SINAR TELEKOM', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Frediansyah', 1, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:19', 'Sales Force'),
(17, 'TPT021', 'Ivan Gustiawan', 'CV. SINAR TELEKOM', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Ivan Gustiawan', 1, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:15', 'Sales Force'),
(18, 'TPT022', 'Nurul Herizen', 'CV. SINAR TELEKOM', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Nurul Herizen', 1, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:10', 'Sales Force'),
(19, 'TPT023', 'Alpi Syahrin', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Alpi Syahrin', 2, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:17:03', 'Sales Force'),
(20, 'TPT024', 'Dani Rahman', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Dani Rahman', 2, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:58', 'Sales Force'),
(21, 'TPT025', 'Jumadil Qubro', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Jumadil Qubro', 2, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:53', 'Sales Force'),
(22, 'TPT026', 'Naufal Daffa Faros', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Naufal Daffa Faros', 2, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:49', 'Sales Force'),
(23, 'TPT027', 'Rian Satria Agung Saputra', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Rian Satria Agung Saputra', 2, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:44', 'Sales Force'),
(24, 'TPT028', 'Sonny Tonando', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Sonny Tonando', 2, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:40', 'Sales Force'),
(25, 'TPT029', 'Agung Maulana', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Agung Maulana', 4, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:36', 'Sales Force'),
(26, 'TPT030', 'Agus Waluyo', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Agus Waluyo', 4, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:31', 'Sales Force'),
(27, 'TPT031', 'Dopri Zuarsyah', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Dopri Zuarsyah', 4, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:25', 'Sales Force'),
(28, 'TPT032', 'Jeni Handika', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Jeni Handika', 4, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:20', 'Sales Force'),
(29, 'TPT033', 'Sholahuddin', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Sholahuddin', 4, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:16', 'Sales Force'),
(30, 'TPT034', 'Triono Susanto', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Triono Susanto', 4, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:11', 'Sales Force'),
(31, 'TPT035', 'Abdul Halim', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Abdul Halim', 48, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:16:00', 'Sales Force'),
(32, 'TPT036', 'Angga Pranata', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Angga Pranata', 48, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:15:54', 'Sales Force'),
(33, 'TPT037', 'David Alex Sander', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'David Alex Sander', 48, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:15:47', 'Sales Force'),
(34, 'TPT038', 'Deni Farizal', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Deni Farizal', 48, 'active', 0.00, '2025-11-11 06:12:19', '2025-11-11 06:15:40', 'Sales Force'),
(35, 'PLYR001', 'SEDAYU', 'SEDAYU', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '08', 'sedayu@sinartelkom.com', 'Ahmad Wahyudi', 47, 'active', 0.00, '2025-11-11 06:41:55', '2025-11-11 06:41:55', 'Player/Pemain'),
(36, 'TPT001', 'Wirahadi Koesuma', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '08', 'wirahadi@sinartelkom.com', 'Pak Wira', 45, 'active', 0.00, '2025-11-11 06:44:38', '2025-11-11 06:44:38', 'General Manager'),
(37, 'TPT002', 'Wirahadi Koesuma', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '08', 'wirahadi@sinartelkom.com', 'Pak Wira', 44, 'active', 0.00, '2025-11-11 06:45:09', '2025-11-11 06:45:09', 'General Manager'),
(38, 'TPT003', 'Irfani', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '08', 'irfani@sinartelkom.com', 'Bang Irfan', 44, 'active', 0.00, '2025-11-11 06:45:53', '2025-11-11 06:45:53', 'Manager'),
(39, 'TPT004', 'Irfani', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '08', 'irfani@sinartelkom.com', 'Bang Irfan', 45, 'active', 0.00, '2025-11-11 06:46:16', '2025-11-11 06:46:16', 'Manager');

-- --------------------------------------------------------

--
-- Table structure for table `setoran_evidence`
--

CREATE TABLE `setoran_evidence` (
  `evidence_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `cabang` varchar(100) NOT NULL,
  `atas_nama` varchar(150) NOT NULL,
  `bank_pengirim` varchar(100) DEFAULT NULL,
  `bank` varchar(100) NOT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `evidence_path` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `setoran_evidence`
--

INSERT INTO `setoran_evidence` (`evidence_id`, `tanggal`, `cabang`, `atas_nama`, `bank_pengirim`, `bank`, `nominal`, `evidence_path`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2025-11-12', 'Warehouse Lubuklinggau', 'Irfani', 'BCA', 'BCA CV', 1650000.00, '/sinartelekomdashboardsystem/assets/images/evidence/evidence_20251112_074545_9_31a18445.png', '', 9, '2025-11-12 06:45:45', '2025-11-12 06:45:45'),
(2, '2025-11-12', 'Warehouse Lubuklinggau', 'Wirahadi Koesuma', 'MANDIRI', 'MANDIRI CV', 500000.00, '/sinartelekomdashboardsystem/assets/images/evidence/evidence_20251112_074609_9_7523f548.png', '', 9, '2025-11-12 06:46:09', '2025-11-12 06:46:09'),
(3, '2025-11-12', 'Warehouse Lahat', 'Irfani', 'BCA', 'BCA CV', 2235000.00, '/sinartelekomdashboardsystem/assets/images/evidence/evidence_20251112_113756_8_e58c17d9.png', '', 8, '2025-11-12 10:37:56', '2025-11-12 10:37:56');

-- --------------------------------------------------------

--
-- Table structure for table `setoran_harian`
--

CREATE TABLE `setoran_harian` (
  `setoran_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `cabang` varchar(100) DEFAULT NULL,
  `reseller` varchar(100) DEFAULT NULL,
  `produk` varchar(200) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `total_setoran` decimal(15,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `setoran_harian`
--

INSERT INTO `setoran_harian` (`setoran_id`, `tanggal`, `cabang`, `reseller`, `produk`, `qty`, `harga_satuan`, `total_setoran`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2025-11-12', 'Warehouse Lubuklinggau', 'Irfani', 'Voucher Fisik Segel Lite', 3000, 500.00, 1500000.00, 9, '2025-11-12 06:44:58', '2025-11-12 06:44:58'),
(2, '2025-11-12', 'Warehouse Lubuklinggau', 'Wirahadi Koesuma', 'Voucher Fisik Segel Lite', 1000, 500.00, 500000.00, 9, '2025-11-12 06:44:58', '2025-11-12 06:44:58'),
(3, '2025-11-12', 'Warehouse Lahat', 'Irfani', 'Voucher Fisik Segel Lite', 3000, 500.00, 1500000.00, 8, '2025-11-12 10:37:37', '2025-11-12 10:37:37'),
(4, '2025-11-12', 'Warehouse Lahat', 'Irfani', 'Voucher Fisik Segel Lite', 150, 500.00, 75000.00, 8, '2025-11-12 10:37:37', '2025-11-12 10:37:37'),
(5, '2025-11-12', 'Warehouse Lahat', 'Irfani', 'Voucher Fisik Segel Lite', 200, 500.00, 100000.00, 8, '2025-11-12 10:37:37', '2025-11-12 10:37:37'),
(6, '2025-11-12', 'Warehouse Lahat', 'Irfani', 'Preload 3GB 30 Hari', 12, 28000.00, 336000.00, 8, '2025-11-12 10:37:37', '2025-11-12 10:37:37'),
(7, '2025-11-12', 'Warehouse Lahat', 'Irfani', 'Voucher Fisik Segel Lite', 500, 500.00, 250000.00, 8, '2025-11-12 10:37:37', '2025-11-12 10:37:37'),
(8, '2025-11-13', 'Warehouse Lahat', 'Irfani', 'Voucher Fisik Segel Lite', 2000, 500.00, 1000000.00, 8, '2025-11-13 18:19:47', '2025-11-13 18:19:47'),
(9, '2025-11-13', 'Warehouse Lahat', 'Irfani', 'Voucher Fisik Segel Lite', 700, 500.00, 350000.00, 8, '2025-11-13 18:19:47', '2025-11-13 18:19:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('administrator','admin','manager','sales','staff','supervisor','finance') DEFAULT 'staff',
  `cabang_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `cabang_id`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(6, 'admin_pagaralam', '$2y$10$.p5mj4Uvg4O7HOVlR3sztuQVRuDmNOz5zf49FMOGrGUULfaA1sC1.', 'Admin TAP Pagar Alam', 'adminpagaralam@sinartelkom.com', '08', 'staff', 1, 'active', '2025-11-08 09:26:52', '2025-11-08 16:29:44', '2025-11-08 16:29:44'),
(7, 'julita_novita', '$2y$10$vcGw07VhUGCFo2SCBUrbt.aXZ1QE4bSKpuKNy7WeivGpdE6dZSE7e', 'Julita Novita Sari', 'julitanovitasari@sinartelkom.com', '08', 'finance', 44, 'active', '2025-11-08 10:11:21', '2025-11-10 08:26:56', '2025-11-08 15:18:44'),
(8, 'ayu_tiara', '$2y$10$RIr77SdYq26H2jo2gWZRWuGUZ14IOONId/kzpHmVZqgbvfWZzZOdC', 'Ayu Tiara Veronica', 'ayutiaraveronica@sinartelkom.com', '08', 'finance', 45, 'active', '2025-11-08 10:15:10', '2025-11-15 15:38:39', '2025-11-15 15:38:39'),
(9, 'rhudychandra', '$2y$10$VGpBhlnehkWynHoaLxsDsOqS6R1UhHKuj8JA.5sL/P4jv3Q8ZIZwG', 'Rhudy Chandra', 'rhudychandra@sinartelkom.com', '081234567890', 'administrator', NULL, 'active', '2025-11-12 06:31:22', '2025-12-02 17:06:32', '2025-12-02 17:06:32'),
(10, 'andrianguntur', '$2y$10$6KcjPtLhBWVr.ESkkhp5A.7G.FmQIODjLVsXc0WBFBv8CSCRacl3e', 'Andrian Guntur', 'andrianguntur@sinartelkom.com', '08', 'supervisor', 2, 'active', '2025-11-08 16:48:40', '2025-11-10 08:27:10', NULL),
(11, 'shela_damayanti', '$2y$10$GM55DgepWnwCE4E2oe9LaOgaOpFsbNmzuCis/wKWuXNuXxjk6O.Wq', 'Shela Damayanti', 'sheladamayanti@sinartelkom.com', '08', 'staff', 4, 'active', '2025-11-09 09:27:12', '2025-11-13 17:49:05', '2025-11-13 17:49:05'),
(12, 'dwifitria_wulandari', '$2y$10$YaFOI/y9fbBh3Ax.SNpv4OcVFRSBmx5C62RnTLm5Pr3e4WtpPda7q', 'Dwi Fitria Wualndari', 'dwifitriawulandari@sinartelkom.com', '08', 'staff', 5, 'active', '2025-11-09 15:04:35', '2025-11-09 15:04:35', NULL),
(13, 'mentari_dwi', '$2y$10$HNBBx3WqfwvU55rMlNlsTOX41dlAW/JCXDvc9oG4EoHwU1AfAHDwu', 'Mentari Dwi Satwika', 'mentaridwi@sinartelkom.com', '08', 'staff', 48, 'active', '2025-11-09 15:07:15', '2025-11-09 15:07:15', NULL),
(14, 'hera_fitriani', '$2y$10$FTfg3S3pZAtUKO42JxQcYONXPKaHDwZSKsgbjn.baxzy2Km6vyyXW', 'Hera Fitriani', 'herafitriani@sinartelkom.com', '08', 'finance', 44, 'active', '2025-11-11 06:47:11', '2025-11-11 06:47:11', NULL),
(15, 'irfani', '$2y$10$gbJLrbP1erbIRe1GmeCpTOsoJ25OTW7C37UFI/0atvsi/M5JwUz5e', 'Irfani', 'irfani@sinartelkom.com', '08', 'manager', NULL, 'active', '2025-11-08 14:09:32', '2025-11-15 20:15:11', '2025-11-15 20:15:11');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_admin_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `view_admin_dashboard` (
`total_cabang` bigint(21)
,`total_reseller` bigint(21)
,`total_users` bigint(21)
,`total_produk` bigint(21)
,`total_penjualan` decimal(37,2)
,`total_stok` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_laporan_penjualan`
-- (See below for the actual view)
--
CREATE TABLE `view_laporan_penjualan` (
`penjualan_id` int(11)
,`no_invoice` varchar(50)
,`tanggal_penjualan` date
,`kode_pelanggan` varchar(50)
,`nama_pelanggan` varchar(200)
,`sales_person` varchar(100)
,`subtotal` decimal(15,2)
,`diskon` decimal(15,2)
,`pajak` decimal(15,2)
,`total` decimal(15,2)
,`metode_pembayaran` enum('Transfer','Cash','Budget Komitmen','Finpay')
,`status_pembayaran` enum('Paid (Lunas)','Pending (Menunggu)','TOP (Term Off Payment)','Cancelled (Dibatalkan)')
,`status_pengiriman` enum('pending','processing','shipped','delivered','cancelled')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_reseller_performance`
-- (See below for the actual view)
--
CREATE TABLE `view_reseller_performance` (
`reseller_id` int(11)
,`kode_reseller` varchar(20)
,`nama_reseller` varchar(100)
,`nama_perusahaan` varchar(100)
,`nama_cabang` varchar(100)
,`total_transaksi` bigint(21)
,`total_pembelian` decimal(37,2)
,`status` enum('active','inactive')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_sales_performance`
-- (See below for the actual view)
--
CREATE TABLE `view_sales_performance` (
`user_id` int(11)
,`username` varchar(50)
,`full_name` varchar(100)
,`role` enum('administrator','admin','manager','sales','staff','supervisor','finance')
,`total_transaksi` bigint(21)
,`total_penjualan` decimal(37,2)
,`rata_rata_transaksi` decimal(19,6)
,`transaksi_terbesar` decimal(15,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_sales_per_cabang`
-- (See below for the actual view)
--
CREATE TABLE `view_sales_per_cabang` (
`cabang_id` int(11)
,`kode_cabang` varchar(20)
,`nama_cabang` varchar(100)
,`kota` varchar(50)
,`total_transaksi` bigint(21)
,`total_penjualan` decimal(37,2)
,`jumlah_reseller` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_stock_per_cabang`
-- (See below for the actual view)
--
CREATE TABLE `view_stock_per_cabang` (
`cabang_id` int(11)
,`kode_cabang` varchar(20)
,`nama_cabang` varchar(100)
,`jumlah_produk` bigint(21)
,`total_stok` decimal(32,0)
,`nilai_stok` decimal(47,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_stok_produk`
-- (See below for the actual view)
--
CREATE TABLE `view_stok_produk` (
`produk_id` int(11)
,`kode_produk` varchar(50)
,`nama_produk` varchar(200)
,`kategori` varchar(100)
,`harga` decimal(15,2)
,`harga_promo` decimal(15,2)
,`stok` int(11)
,`status` enum('active','inactive','discontinued')
,`status_stok` varchar(12)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_top_selling_products`
-- (See below for the actual view)
--
CREATE TABLE `view_top_selling_products` (
`produk_id` int(11)
,`kode_produk` varchar(50)
,`nama_produk` varchar(200)
,`kategori` varchar(100)
,`jumlah_transaksi` bigint(21)
,`total_terjual` decimal(32,0)
,`total_revenue` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Structure for view `view_admin_dashboard`
--
DROP TABLE IF EXISTS `view_admin_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_admin_dashboard`  AS SELECT (select count(0) from `cabang` where `cabang`.`status` = 'active') AS `total_cabang`, (select count(0) from `reseller` where `reseller`.`status` = 'active') AS `total_reseller`, (select count(0) from `users` where `users`.`status` = 'active') AS `total_users`, (select count(0) from `produk`) AS `total_produk`, (select coalesce(sum(`penjualan`.`total`),0) from `penjualan` where `penjualan`.`status_pembayaran` = 'paid') AS `total_penjualan`, (select coalesce(sum(`produk`.`stok`),0) from `produk`) AS `total_stok` ;

-- --------------------------------------------------------

--
-- Structure for view `view_laporan_penjualan`
--
DROP TABLE IF EXISTS `view_laporan_penjualan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_laporan_penjualan`  AS SELECT `p`.`penjualan_id` AS `penjualan_id`, `p`.`no_invoice` AS `no_invoice`, `p`.`tanggal_penjualan` AS `tanggal_penjualan`, `pel`.`kode_pelanggan` AS `kode_pelanggan`, `pel`.`nama_pelanggan` AS `nama_pelanggan`, `u`.`full_name` AS `sales_person`, `p`.`subtotal` AS `subtotal`, `p`.`diskon` AS `diskon`, `p`.`pajak` AS `pajak`, `p`.`total` AS `total`, `p`.`metode_pembayaran` AS `metode_pembayaran`, `p`.`status_pembayaran` AS `status_pembayaran`, `p`.`status_pengiriman` AS `status_pengiriman`, `p`.`created_at` AS `created_at` FROM ((`penjualan` `p` join `pelanggan` `pel` on(`p`.`pelanggan_id` = `pel`.`pelanggan_id`)) join `users` `u` on(`p`.`user_id` = `u`.`user_id`)) ORDER BY `p`.`tanggal_penjualan` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_reseller_performance`
--
DROP TABLE IF EXISTS `view_reseller_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_reseller_performance`  AS SELECT `r`.`reseller_id` AS `reseller_id`, `r`.`kode_reseller` AS `kode_reseller`, `r`.`nama_reseller` AS `nama_reseller`, `r`.`nama_perusahaan` AS `nama_perusahaan`, `c`.`nama_cabang` AS `nama_cabang`, count(`p`.`penjualan_id`) AS `total_transaksi`, coalesce(sum(`p`.`total`),0) AS `total_pembelian`, `r`.`status` AS `status` FROM ((`reseller` `r` left join `cabang` `c` on(`r`.`cabang_id` = `c`.`cabang_id`)) left join `penjualan` `p` on(`r`.`reseller_id` = `p`.`reseller_id` and `p`.`status_pembayaran` = 'paid')) GROUP BY `r`.`reseller_id`, `r`.`kode_reseller`, `r`.`nama_reseller`, `r`.`nama_perusahaan`, `c`.`nama_cabang`, `r`.`status` ;

-- --------------------------------------------------------

--
-- Structure for view `view_sales_performance`
--
DROP TABLE IF EXISTS `view_sales_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_sales_performance`  AS SELECT `u`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, `u`.`role` AS `role`, count(`p`.`penjualan_id`) AS `total_transaksi`, sum(`p`.`total`) AS `total_penjualan`, avg(`p`.`total`) AS `rata_rata_transaksi`, max(`p`.`total`) AS `transaksi_terbesar` FROM (`users` `u` left join `penjualan` `p` on(`u`.`user_id` = `p`.`user_id` and `p`.`status_pembayaran` = 'paid')) WHERE `u`.`role` in ('sales','manager') GROUP BY `u`.`user_id`, `u`.`username`, `u`.`full_name`, `u`.`role` ORDER BY sum(`p`.`total`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_sales_per_cabang`
--
DROP TABLE IF EXISTS `view_sales_per_cabang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_sales_per_cabang`  AS SELECT `c`.`cabang_id` AS `cabang_id`, `c`.`kode_cabang` AS `kode_cabang`, `c`.`nama_cabang` AS `nama_cabang`, `c`.`kota` AS `kota`, count(distinct `p`.`penjualan_id`) AS `total_transaksi`, coalesce(sum(`p`.`total`),0) AS `total_penjualan`, count(distinct `r`.`reseller_id`) AS `jumlah_reseller` FROM ((`cabang` `c` left join `penjualan` `p` on(`c`.`cabang_id` = `p`.`cabang_id` and `p`.`status_pembayaran` = 'paid')) left join `reseller` `r` on(`c`.`cabang_id` = `r`.`cabang_id` and `r`.`status` = 'active')) GROUP BY `c`.`cabang_id`, `c`.`kode_cabang`, `c`.`nama_cabang`, `c`.`kota` ;

-- --------------------------------------------------------

--
-- Structure for view `view_stock_per_cabang`
--
DROP TABLE IF EXISTS `view_stock_per_cabang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_stock_per_cabang`  AS SELECT `c`.`cabang_id` AS `cabang_id`, `c`.`kode_cabang` AS `kode_cabang`, `c`.`nama_cabang` AS `nama_cabang`, count(distinct `pr`.`produk_id`) AS `jumlah_produk`, coalesce(sum(`pr`.`stok`),0) AS `total_stok`, coalesce(sum(`pr`.`stok` * `pr`.`harga`),0) AS `nilai_stok` FROM (`cabang` `c` left join `produk` `pr` on(`c`.`cabang_id` = `pr`.`cabang_id`)) GROUP BY `c`.`cabang_id`, `c`.`kode_cabang`, `c`.`nama_cabang` ;

-- --------------------------------------------------------

--
-- Structure for view `view_stok_produk`
--
DROP TABLE IF EXISTS `view_stok_produk`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_stok_produk`  AS SELECT `pr`.`produk_id` AS `produk_id`, `pr`.`kode_produk` AS `kode_produk`, `pr`.`nama_produk` AS `nama_produk`, `pr`.`kategori` AS `kategori`, `pr`.`harga` AS `harga`, `pr`.`harga_promo` AS `harga_promo`, `pr`.`stok` AS `stok`, `pr`.`status` AS `status`, CASE WHEN `pr`.`stok` <= 10 THEN 'Low Stock' WHEN `pr`.`stok` <= 50 THEN 'Medium Stock' ELSE 'High Stock' END AS `status_stok` FROM `produk` AS `pr` WHERE `pr`.`status` = 'active' ORDER BY `pr`.`stok` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `view_top_selling_products`
--
DROP TABLE IF EXISTS `view_top_selling_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_top_selling_products`  AS SELECT `pr`.`produk_id` AS `produk_id`, `pr`.`kode_produk` AS `kode_produk`, `pr`.`nama_produk` AS `nama_produk`, `pr`.`kategori` AS `kategori`, count(`dp`.`detail_id`) AS `jumlah_transaksi`, sum(`dp`.`jumlah`) AS `total_terjual`, sum(`dp`.`subtotal`) AS `total_revenue` FROM ((`produk` `pr` join `detail_penjualan` `dp` on(`pr`.`produk_id` = `dp`.`produk_id`)) join `penjualan` `p` on(`dp`.`penjualan_id` = `p`.`penjualan_id`)) WHERE `p`.`status_pembayaran` = 'paid' GROUP BY `pr`.`produk_id`, `pr`.`kode_produk`, `pr`.`nama_produk`, `pr`.`kategori` ORDER BY sum(`dp`.`subtotal`) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cabang`
--
ALTER TABLE `cabang`
  ADD PRIMARY KEY (`cabang_id`),
  ADD UNIQUE KEY `kode_cabang` (`kode_cabang`);

--
-- Indexes for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `idx_penjualan_id` (`penjualan_id`),
  ADD KEY `idx_produk_id` (`produk_id`),
  ADD KEY `idx_detail_penjualan_produk` (`produk_id`,`penjualan_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_produk_id` (`produk_id`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_tipe_transaksi` (`tipe_transaksi`),
  ADD KEY `idx_inventory_produk_tanggal` (`produk_id`,`tanggal`),
  ADD KEY `cabang_id` (`cabang_id`),
  ADD KEY `idx_status_approval` (`status_approval`);

--
-- Indexes for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  ADD PRIMARY KEY (`kategori_id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_nama` (`nama_kategori`);

--
-- Indexes for table `outlet`
--
ALTER TABLE `outlet`
  ADD PRIMARY KEY (`outlet_id`),
  ADD UNIQUE KEY `nomor_rs` (`nomor_rs`),
  ADD KEY `idx_nomor_rs` (`nomor_rs`),
  ADD KEY `idx_sales_force` (`sales_force_id`),
  ADD KEY `idx_cabang` (`cabang_id`),
  ADD KEY `idx_status_outlet` (`status_outlet`),
  ADD KEY `idx_jenis_rs` (`jenis_rs`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_kecamatan` (`kecamatan`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`pelanggan_id`),
  ADD UNIQUE KEY `kode_pelanggan` (`kode_pelanggan`),
  ADD KEY `idx_kode_pelanggan` (`kode_pelanggan`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_tipe_pelanggan` (`tipe_pelanggan`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `pengajuan_stock`
--
ALTER TABLE `pengajuan_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rs_type` (`rs_type`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_outlet` (`outlet_id`),
  ADD KEY `idx_requester` (`requester_id`),
  ADD KEY `idx_warehouse` (`warehouse_id`);

--
-- Indexes for table `pengajuan_stock_items`
--
ALTER TABLE `pengajuan_stock_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pengajuan` (`pengajuan_id`),
  ADD KEY `idx_produk` (`produk_id`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`penjualan_id`),
  ADD UNIQUE KEY `no_invoice` (`no_invoice`),
  ADD KEY `idx_no_invoice` (`no_invoice`),
  ADD KEY `idx_tanggal_penjualan` (`tanggal_penjualan`),
  ADD KEY `idx_pelanggan_id` (`pelanggan_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status_pembayaran` (`status_pembayaran`),
  ADD KEY `idx_status_pengiriman` (`status_pengiriman`),
  ADD KEY `idx_penjualan_tanggal_status` (`tanggal_penjualan`,`status_pembayaran`),
  ADD KEY `cabang_id` (`cabang_id`),
  ADD KEY `reseller_id` (`reseller_id`);

--
-- Indexes for table `penjualan_outlet`
--
ALTER TABLE `penjualan_outlet`
  ADD PRIMARY KEY (`penjualan_outlet_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_sales_force` (`sales_force_id`),
  ADD KEY `idx_produk` (`produk_id`),
  ADD KEY `idx_outlet` (`outlet_id`),
  ADD KEY `idx_cabang` (`cabang_id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`produk_id`),
  ADD UNIQUE KEY `kode_produk` (`kode_produk`),
  ADD KEY `idx_kode_produk` (`kode_produk`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `cabang_id` (`cabang_id`),
  ADD KEY `idx_kategori_id` (`kategori_id`),
  ADD KEY `idx_produk_hpp_saldo` (`hpp_saldo`),
  ADD KEY `idx_produk_hpp_fisik` (`hpp_fisik`);

--
-- Indexes for table `reseller`
--
ALTER TABLE `reseller`
  ADD PRIMARY KEY (`reseller_id`),
  ADD UNIQUE KEY `kode_reseller` (`kode_reseller`),
  ADD KEY `cabang_id` (`cabang_id`);

--
-- Indexes for table `setoran_evidence`
--
ALTER TABLE `setoran_evidence`
  ADD PRIMARY KEY (`evidence_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_cabang` (`cabang`),
  ADD KEY `idx_bank` (`bank`),
  ADD KEY `idx_setoran_evidence_nominal` (`nominal`),
  ADD KEY `idx_setoran_evidence_bank_pengirim` (`bank_pengirim`);

--
-- Indexes for table `setoran_harian`
--
ALTER TABLE `setoran_harian`
  ADD PRIMARY KEY (`setoran_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_cabang` (`cabang`),
  ADD KEY `idx_reseller` (`reseller`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_tanggal_cabang` (`tanggal`,`cabang`),
  ADD KEY `idx_tanggal_reseller` (`tanggal`,`reseller`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `cabang_id` (`cabang_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cabang`
--
ALTER TABLE `cabang`
  MODIFY `cabang_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  MODIFY `kategori_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `outlet`
--
ALTER TABLE `outlet`
  MODIFY `outlet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `pelanggan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pengajuan_stock`
--
ALTER TABLE `pengajuan_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `pengajuan_stock_items`
--
ALTER TABLE `pengajuan_stock_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `penjualan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `penjualan_outlet`
--
ALTER TABLE `penjualan_outlet`
  MODIFY `penjualan_outlet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `produk_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `reseller`
--
ALTER TABLE `reseller`
  MODIFY `reseller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `setoran_evidence`
--
ALTER TABLE `setoran_evidence`
  MODIFY `evidence_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `setoran_harian`
--
ALTER TABLE `setoran_harian`
  MODIFY `setoran_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualan` (`penjualan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`produk_id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`produk_id`),
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `inventory_ibfk_3` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_ibfk_4` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL;

--
-- Constraints for table `outlet`
--
ALTER TABLE `outlet`
  ADD CONSTRAINT `outlet_ibfk_1` FOREIGN KEY (`sales_force_id`) REFERENCES `reseller` (`reseller_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `outlet_ibfk_2` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL;

--
-- Constraints for table `pengajuan_stock_items`
--
ALTER TABLE `pengajuan_stock_items`
  ADD CONSTRAINT `fk_pengajuan_items_header` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan_stock` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`pelanggan_id`),
  ADD CONSTRAINT `penjualan_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `penjualan_ibfk_3` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `penjualan_ibfk_4` FOREIGN KEY (`reseller_id`) REFERENCES `reseller` (`reseller_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `penjualan_ibfk_5` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `penjualan_ibfk_6` FOREIGN KEY (`reseller_id`) REFERENCES `reseller` (`reseller_id`) ON DELETE SET NULL;

--
-- Constraints for table `penjualan_outlet`
--
ALTER TABLE `penjualan_outlet`
  ADD CONSTRAINT `penjualan_outlet_ibfk_1` FOREIGN KEY (`sales_force_id`) REFERENCES `reseller` (`reseller_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualan_outlet_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`produk_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualan_outlet_ibfk_3` FOREIGN KEY (`outlet_id`) REFERENCES `outlet` (`outlet_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualan_outlet_ibfk_4` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `penjualan_outlet_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `produk_ibfk_2` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL;

--
-- Constraints for table `reseller`
--
ALTER TABLE `reseller`
  ADD CONSTRAINT `reseller_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL;

--
-- Constraints for table `setoran_evidence`
--
ALTER TABLE `setoran_evidence`
  ADD CONSTRAINT `setoran_evidence_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `setoran_harian`
--
ALTER TABLE `setoran_harian`
  ADD CONSTRAINT `setoran_harian_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
