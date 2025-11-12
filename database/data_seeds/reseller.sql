-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 12, 2025 at 06:43 AM
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `kategori` enum('Sales Force','General Manager','Manager','Supervisor','Player/Pemain','Merchant') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `reseller`
--

INSERT INTO `reseller` (`reseller_id`, `kode_reseller`, `nama_reseller`, `nama_perusahaan`, `alamat`, `kota`, `provinsi`, `telepon`, `email`, `contact_person`, `cabang_id`, `status`, `total_penjualan`, `created_at`, `updated_at`, `kategori`) VALUES
(1, 'TPT005', 'Andrian Guntur', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Andrian Guntur', 2, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:19:03', 'Supervisor'),
(2, 'TPT006', 'Wahyu Hidayat', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Wahyu Hidayat', 5, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:18:58', 'Supervisor'),
(3, 'TPT007', 'Perli', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Perli', 4, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:18:51', 'Supervisor'),
(4, 'TPT008', 'Dody Delta', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Dody Delta', 17, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:18:46', 'Supervisor'),
(5, 'TPT009', 'Ahmad Rifko', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Ahmad Rifko', 48, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:18:39', 'Supervisor'),
(6, 'TPT010', 'Hari Priyanto', 'CV. SINAR TELEKOM', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Hari Priyanto', 1, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:18:30', 'Supervisor'),
(7, 'TPT011', 'Jeni Handika', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'supervisor@sinartelkom.com', 'Jeni Handika', 18, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:18:22', 'Supervisor'),
(8, 'TPT012', 'Donal Septian', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Donal Septian', 18, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:18:13', 'Sales Force'),
(9, 'TPT013', 'Ego Saputra', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Ego Saputra', 18, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:18:06', 'Sales Force'),
(10, 'TPT014', 'Kgs Jauhari Fikri', 'CV. SINAR TELEKOM', 'Empat Lawang', 'Empat Lawang', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Kgs Jauhari Fikri', 17, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:57', 'Sales Force'),
(11, 'TPT015', 'Angga Ihza Rambe', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Angga Ihza Rambe', 5, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:50', 'Sales Force'),
(12, 'TPT016', 'Angga Triska', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Angga Triska', 5, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:39', 'Sales Force'),
(13, 'TPT017', 'Derry Setyadi', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Derry Setyadi', 5, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:34', 'Sales Force'),
(14, 'TPT018', 'Jaka Umbara', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Jaka Umbara', 5, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:29', 'Sales Force'),
(15, 'TPT019', 'Leo Waldi', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Leo Waldi', 5, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:24', 'Sales Force'),
(16, 'TPT020', 'Frediansyah', 'CV. SINAR TELEKOM', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Frediansyah', 1, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:19', 'Sales Force'),
(17, 'TPT021', 'Ivan Gustiawan', 'CV. SINAR TELEKOM', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Ivan Gustiawan', 1, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:15', 'Sales Force'),
(18, 'TPT022', 'Nurul Herizen', 'CV. SINAR TELEKOM', 'Kota Pagar Alam', 'Kota Pagar Alam', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Nurul Herizen', 1, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:10', 'Sales Force'),
(19, 'TPT023', 'Alpi Syahrin', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Alpi Syahrin', 2, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:17:03', 'Sales Force'),
(20, 'TPT024', 'Dani Rahman', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Dani Rahman', 2, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:58', 'Sales Force'),
(21, 'TPT025', 'Jumadil Qubro', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Jumadil Qubro', 2, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:53', 'Sales Force'),
(22, 'TPT026', 'Naufal Daffa Faros', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Naufal Daffa Faros', 2, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:49', 'Sales Force'),
(23, 'TPT027', 'Rian Satria Agung Saputra', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Rian Satria Agung Saputra', 2, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:44', 'Sales Force'),
(24, 'TPT028', 'Sonny Tonando', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Sonny Tonando', 2, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:40', 'Sales Force'),
(25, 'TPT029', 'Agung Maulana', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Agung Maulana', 4, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:36', 'Sales Force'),
(26, 'TPT030', 'Agus Waluyo', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Agus Waluyo', 4, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:31', 'Sales Force'),
(27, 'TPT031', 'Dopri Zuarsyah', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Dopri Zuarsyah', 4, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:25', 'Sales Force'),
(28, 'TPT032', 'Jeni Handika', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Jeni Handika', 4, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:20', 'Sales Force'),
(29, 'TPT033', 'Sholahuddin', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Sholahuddin', 4, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:16', 'Sales Force'),
(30, 'TPT034', 'Triono Susanto', 'CV. SINAR TELEKOM', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Triono Susanto', 4, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:11', 'Sales Force'),
(31, 'TPT035', 'Abdul Halim', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Abdul Halim', 48, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:16:00', 'Sales Force'),
(32, 'TPT036', 'Angga Pranata', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Angga Pranata', 48, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:15:54', 'Sales Force'),
(33, 'TPT037', 'David Alex Sander', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'David Alex Sander', 48, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:15:47', 'Sales Force'),
(34, 'TPT038', 'Deni Farizal', 'CV. SINAR TELEKOM', 'Musi Rawas Utara', 'Musi Rawas Utara', 'Sumatera Selatan', '8', 'salesforce@sinartelkom.com', 'Deni Farizal', 48, 'active', 0.00, '2025-11-11 13:12:19', '2025-11-11 13:15:40', 'Sales Force'),
(35, 'PLYR001', 'SEDAYU', 'SEDAYU', 'Musi Rawas', 'Musi Rawas', 'Sumatera Selatan', '08', 'sedayu@sinartelkom.com', 'Ahmad Wahyudi', 47, 'active', 0.00, '2025-11-11 13:41:55', '2025-11-11 13:41:55', 'Player/Pemain'),
(36, 'TPT001', 'Wirahadi Koesuma', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '08', 'wirahadi@sinartelkom.com', 'Pak Wira', 45, 'active', 0.00, '2025-11-11 13:44:38', '2025-11-11 13:44:38', 'General Manager'),
(37, 'TPT002', 'Wirahadi Koesuma', 'CV. SINAR TELEKOM', 'Lahat', 'Lahat', 'Sumatera Selatan', '08', 'wirahadi@sinartelkom.com', 'Pak Wira', 44, 'active', 0.00, '2025-11-11 13:45:09', '2025-11-11 13:45:09', 'General Manager'),
(38, 'TPT003', 'Irfani', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '08', 'irfani@sinartelkom.com', 'Bang Irfan', 44, 'active', 0.00, '2025-11-11 13:45:53', '2025-11-11 13:45:53', 'Manager'),
(39, 'TPT004', 'Irfani', 'CV. SINAR TELEKOM', 'Kota Lubuklinggau', 'Kota Lubuklinggau', 'Sumatera Selatan', '08', 'irfani@sinartelkom.com', 'Bang Irfan', 45, 'active', 0.00, '2025-11-11 13:46:16', '2025-11-11 13:46:16', 'Manager');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reseller`
--
ALTER TABLE `reseller`
  ADD PRIMARY KEY (`reseller_id`),
  ADD UNIQUE KEY `kode_reseller` (`kode_reseller`),
  ADD KEY `cabang_id` (`cabang_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reseller`
--
ALTER TABLE `reseller`
  MODIFY `reseller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reseller`
--
ALTER TABLE `reseller`
  ADD CONSTRAINT `reseller_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
