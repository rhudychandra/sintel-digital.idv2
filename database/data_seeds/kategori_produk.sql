-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 05:09 PM
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
-- Table structure for table `kategori_produk`
--

CREATE TABLE `kategori_produk` (
  `kategori_id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT '?',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_produk`
--

INSERT INTO `kategori_produk` (`kategori_id`, `nama_kategori`, `deskripsi`, `icon`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Voucher Internet Lite', 'Voucher Fisik Internet Lite', '🔖', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(2, 'Voucher Internet ByU', 'Voucher Internet ByU', '🩹', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(3, 'Perdana Internet Lite', 'Perdana Internet Lite', '📕', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(4, 'Perdana Internet ByU', 'Perdana Internet ByU', '📘', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(5, 'LinkAja', 'Saldo LinkAja', '💴', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(6, 'Finpay', 'Saldo Finpay', '💶', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(7, 'Voucher Segel Lite', 'Voucher Segel Lite', '♦️', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(8, 'Voucher Segel ByU', 'Voucher Segel ByU', '♠️', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(9, 'Perdana Segel Red 0K', 'Perdana Segel Red 0K', '📕', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(10, 'Perdana Segel ByU 0K', 'Perdana Segel ByU 0K', '📘', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44'),
(11, 'Perdana Internet Special', 'Perdana Internet Special (Nomor Cantik 260GB)', '💎', 'active', '2025-11-10 15:14:44', '2025-11-10 15:14:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  ADD PRIMARY KEY (`kategori_id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  MODIFY `kategori_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
