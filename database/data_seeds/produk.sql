-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 12, 2025 at 10:08 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u879436580_sintel_db1`
--

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `produk_id` int(11) NOT NULL,
  `kode_produk` varchar(50) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(15,2) NOT NULL,
  `harga_promo` decimal(15,2) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `satuan` varchar(20) DEFAULT 'unit',
  `cabang_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`produk_id`, `kode_produk`, `nama_produk`, `kategori`, `deskripsi`, `harga`, `harga_promo`, `stok`, `satuan`, `cabang_id`, `status`, `created_at`, `updated_at`) VALUES
(2, 'VF-Lite-Inet -001', 'VF Internet Lite 1,5GB 3Hari', 'Voucher Internet Lite', 'VF Internet Lite 1,5GB 3Hari', 8002.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(3, 'VF-Lite-Inet -002', 'VF Internet Lite 2GB 3Hari', 'Voucher Internet Lite', 'VF Internet Lite 2GB 3Hari', 10120.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(4, 'VF-Lite-Inet -003', 'VF Internet Lite 3GB 3Hari', 'Voucher Internet Lite', 'VF Internet Lite 3GB 3Hari', 11300.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(5, 'VF-Lite-Inet -004', 'VF Internet Lite 1GB 1Hari', 'Voucher Internet Lite', 'VF Internet Lite 1GB 1Hari', 5500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(6, 'VF-Lite-Inet -005', 'VF Internet Lite 2GB 5Hari', 'Voucher Internet Lite', 'VF Internet Lite 2GB 5Hari', 11400.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(7, 'VF-Lite-Inet -006', 'VF Internet Lite 2,5GB 5Hari', 'Voucher Internet Lite', 'VF Internet Lite 2,5GB 5Hari', 10700.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(8, 'VF-Lite-Inet -007', 'VF Internet Lite 3GB 5Hari', 'Voucher Internet Lite', 'VF Internet Lite 3GB 5Hari', 12520.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(9, 'VF-Lite-Inet -008', 'VF Internet Lite 5GB 5Hari', 'Voucher Internet Lite', 'VF Internet Lite 5GB 5Hari', 21300.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(10, 'VF-Lite-Inet -009', 'VF Internet Lite 7GB 7Hari', 'Voucher Internet Lite', 'VF Internet Lite 7GB 7Hari', 25500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(11, 'VF-Lite-Inet -010', 'VF Internet ByU ByU 1GB 1Hari', 'Voucher Internet ByU', 'VF Internet ByU ByU 1GB 1Hari', 3500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(12, 'VF-Lite-Inet -011', 'VF Internet ByU ByU 2GB 1Hari', 'Voucher Internet ByU', 'VF Internet ByU ByU 2GB 1Hari', 6000.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(13, 'VF-ByU-Inet -001', 'VF Internet ByU ByU 3GB 3Hari', 'Voucher Internet ByU', 'VF Internet ByU ByU 3GB 3Hari', 8500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(14, 'VF-ByU-Inet -002', 'VF Internet ByU ByU 4GB 7Hari', 'Voucher Internet ByU', 'VF Internet ByU ByU 4GB 7Hari', 10500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(15, 'VF-ByU-Inet -003', 'VF Internet ByU ByU 9GB 30Hari', 'Voucher Internet ByU', 'VF Internet ByU ByU 9GB 30Hari', 25500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(16, 'VF-ByU-Inet -004', 'VF Internet ByU ByU 7GB 30Hari', 'Voucher Internet ByU', 'VF Internet ByU ByU 7GB 30Hari', 15500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(17, 'SA-PRL-3GB', 'Preload 3GB 30 Hari', 'Perdana Internet Lite', 'Preload 3GB 30 Hari', 28000.00, NULL, 3900, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 15:17:29'),
(18, 'SA-INET-3GB', 'Perdana Internet SA 3GB', 'Perdana Internet Lite', 'Perdana Internet SA 3GB', 28000.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(19, 'SA-BYU-3GB', 'SA ByU 3GB 30D', 'Perdana Internet ByU', 'SA ByU 3GB 30D', 28000.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(20, 'SA-BYU-3GB-A', 'SA ByU 3GB 30D (Alladin)', 'Perdana Internet ByU', 'SA ByU 3GB 30D (Alladin)', 15000.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(21, 'Link', 'Saldo LinkAja', 'LinkAja', 'Saldo LinkAja', 1.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(22, 'Finpay', 'Saldo Finpay', 'Finpay', 'Saldo Finpay', 1.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(23, 'RED-1', 'Perdana Segel Red 0K', 'Perdana Segel Red 0K', 'Perdana Segel Red 0K', 10000.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(24, 'BYU-1', 'Perdana Segel ByU 0K', 'Perdana Segel ByU 0K', 'Perdana Segel ByU 0K', 10000.00, NULL, 950, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-12 05:54:25'),
(25, 'VF-Lite', 'Voucher Fisik Segel Lite', 'Voucher Segel Lite', 'Voucher Fisik Segel Lite', 500.00, NULL, 338000, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 15:17:29'),
(26, 'VF-ByU', 'Voucher Fisik Segel ByU', 'Voucher Segel ByU', 'Voucher Fisik Segel ByU', 500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:38:21', '2025-11-11 13:38:21'),
(27, 'PRD-1762868390-308', 'VF Internet Lite 5GB 2Hari', 'Voucher Internet Lite', 'VF Internet Lite 5GB 2Hari', 8000.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:39:50', '2025-11-11 13:39:50'),
(28, 'PRD-1762868413-667', 'VF Internet Lite 1GB 24Jam', 'Voucher Internet Lite', 'VF Internet Lite 1GB 24Jam', 4500.00, NULL, 0, 'unit', NULL, 'active', '2025-11-11 13:40:13', '2025-11-11 13:40:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`produk_id`),
  ADD UNIQUE KEY `kode_produk` (`kode_produk`),
  ADD KEY `idx_kode_produk` (`kode_produk`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `cabang_id` (`cabang_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `produk_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
