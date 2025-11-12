-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 11, 2025 at 12:41 PM
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
-- Table structure for table `cabang`
--

CREATE TABLE `cabang` (
  `cabang_id` int(11) NOT NULL,
  `kode_cabang` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_cabang` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `provinsi` varchar(50) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `cabang`
--

INSERT INTO `cabang` (`cabang_id`, `kode_cabang`, `nama_cabang`, `alamat`, `kota`, `provinsi`, `telepon`, `email`, `manager_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PGA', 'TAP Kota Pagar Alam', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '08', 'pagaralam@sinartelkom.com', 'Hari Priyanto', 'active', '2025-11-08 04:33:12', '2025-11-08 12:10:19'),
(2, 'LHT', 'TAP Lahat', 'Lahat', 'Lahat', 'Sumatera Selatan', '08', 'lahat@sinartelkom.com', 'Andrian Guntur', 'active', '2025-11-08 04:33:12', '2025-11-08 12:09:40'),
(4, 'MRS', 'TAP Musi Rawas', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '08', 'musirawas@sinartelkom.com', 'Perli', 'active', '2025-11-08 04:33:12', '2025-11-08 12:48:20'),
(5, 'LLG', 'TAP Kota Lubuklinggau', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '08', 'taplubuklinggau@sinartelkom.com', 'Wahyu Hidayat', 'active', '2025-11-08 04:33:12', '2025-11-08 12:07:55'),
(17, 'EMPL', 'TAP Empat Lawang (Tebing Tinggi)', 'Tebing Tinggi Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '08', 'empatlawangtbg@sinartelkom.com', 'Doddy Delta', 'active', '2025-11-08 12:11:27', '2025-11-08 12:11:27'),
(18, 'PDP', 'TAP Empat Lawang (Pendopo)', 'Pendopo Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '08', 'empatlawangpdp@sinartelkom.com', 'Jeni Handika', 'active', '2025-11-08 12:12:08', '2025-11-09 18:50:55'),
(44, 'WRHLLG', 'Warehouse Lubuklinggau', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '08', 'warehousllg@sinartelkom.com', 'Irfani', 'active', '2025-11-08 17:10:02', '2025-11-08 17:10:02'),
(45, 'WRHLHT', 'Warehouse Lahat', 'Lahat', 'Lahat', 'Sumatera Selatan', '08', 'warehouslht@sinartelkom.com', 'Irfani', 'active', '2025-11-08 17:10:39', '2025-11-08 17:10:39'),
(46, 'OUTER', 'Out Cluster', 'Out Cluster New Musi Rawas', 'Semua Kota', 'Semua Provinsi', '08', 'Outer@sinartelkom.com', 'Wirahadi Koesuma', 'active', '2025-11-09 18:33:23', '2025-11-09 18:33:23'),
(47, 'INNER', 'Player Inner', 'Seluruh City Cluster New Musi Rawas', 'Seluruh City Cluster New Musi Rawas', 'Sumatera Selatan', '08', 'playerinner@sinartelkom.com', 'Wirahadi Koesuma', 'active', '2025-11-09 18:34:29', '2025-11-09 18:34:29'),
(48, 'MRSU', 'TAP Musi Rawas Utara', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '08', 'musirawasutara@sinartelkom.com', 'Ahmad Rifko', 'active', '2025-11-09 18:52:02', '2025-11-09 18:52:02');

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cabang`
--
ALTER TABLE `cabang`
  MODIFY `cabang_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
