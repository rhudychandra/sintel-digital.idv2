-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 12, 2025 at 06:40 AM
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `cabang_id`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(5, 'rhudychandra', '$2y$10$/JRsIE4wBLXoSwmI9xyhF.XZsVwIk1EGzuXpQN/RKi2uV2PEiNjOO', 'Rhudy Chandra Sastra', 'rhudychandra@example.com', '081367777765', 'administrator', NULL, 'active', '2025-11-10 14:28:21', '2025-11-12 05:46:15', '2025-11-12 05:46:15'),
(6, 'admin_pagaralam', '$2y$10$.p5mj4Uvg4O7HOVlR3sztuQVRuDmNOz5zf49FMOGrGUULfaA1sC1.', 'Admin TAP Pagar Alam', 'adminpagaralam@sinartelkom.com', '08', 'staff', 1, 'active', '2025-11-08 16:26:52', '2025-11-08 23:29:44', '2025-11-08 23:29:44'),
(7, 'julita_novita', '$2y$10$vcGw07VhUGCFo2SCBUrbt.aXZ1QE4bSKpuKNy7WeivGpdE6dZSE7e', 'Julita Novita Sari', 'julitanovitasari@sinartelkom.com', '08', 'finance', 44, 'active', '2025-11-08 17:11:21', '2025-11-10 15:26:56', '2025-11-08 22:18:44'),
(8, 'ayu_tiara', '$2y$10$RIr77SdYq26H2jo2gWZRWuGUZ14IOONId/kzpHmVZqgbvfWZzZOdC', 'Ayu Tiara Veronica', 'ayutiaraveronica@sinartelkom.com', '08', 'finance', 45, 'active', '2025-11-08 17:15:10', '2025-11-11 15:15:21', '2025-11-11 15:15:21'),
(9, 'irfani', '$2y$10$gbJLrbP1erbIRe1GmeCpTOsoJ25OTW7C37UFI/0atvsi/M5JwUz5e', 'Irfani', 'irfani@sinartelkom.com', '08', 'manager', 5, 'active', '2025-11-08 21:09:32', '2025-11-12 02:17:28', '2025-11-12 02:17:28'),
(10, 'andrianguntur', '$2y$10$6KcjPtLhBWVr.ESkkhp5A.7G.FmQIODjLVsXc0WBFBv8CSCRacl3e', 'Andrian Guntur', 'andrianguntur@sinartelkom.com', '08', 'supervisor', 2, 'active', '2025-11-08 23:48:40', '2025-11-10 15:27:10', NULL),
(11, 'shela_damayanti', '$2y$10$GM55DgepWnwCE4E2oe9LaOgaOpFsbNmzuCis/wKWuXNuXxjk6O.Wq', 'Shela Damayanti', 'sheladamayanti@sinartelkom.com', '08', 'staff', 4, 'active', '2025-11-09 16:27:12', '2025-11-09 19:03:53', '2025-11-09 19:03:53'),
(12, 'dwifitria_wulandari', '$2y$10$YaFOI/y9fbBh3Ax.SNpv4OcVFRSBmx5C62RnTLm5Pr3e4WtpPda7q', 'Dwi Fitria Wualndari', 'dwifitriawulandari@sinartelkom.com', '08', 'staff', 5, 'active', '2025-11-09 22:04:35', '2025-11-09 22:04:35', NULL),
(13, 'mentari_dwi', '$2y$10$HNBBx3WqfwvU55rMlNlsTOX41dlAW/JCXDvc9oG4EoHwU1AfAHDwu', 'Mentari Dwi Satwika', 'mentaridwi@sinartelkom.com', '08', 'staff', 48, 'active', '2025-11-09 22:07:15', '2025-11-09 22:07:15', NULL),
(14, 'hera_fitriani', '$2y$10$FTfg3S3pZAtUKO42JxQcYONXPKaHDwZSKsgbjn.baxzy2Km6vyyXW', 'Hera Fitriani', 'herafitriani@sinartelkom.com', '08', 'finance', 44, 'active', '2025-11-11 13:47:11', '2025-11-11 13:47:11', NULL);

--
-- Indexes for dumped tables
--

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
  ADD KEY `idx_cabang_id` (`cabang_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
