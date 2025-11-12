-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sinar_telkom_dashboard
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cabang`
--

DROP TABLE IF EXISTS `cabang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cabang` (
  `cabang_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cabang_id`),
  UNIQUE KEY `kode_cabang` (`kode_cabang`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cabang`
--

LOCK TABLES `cabang` WRITE;
/*!40000 ALTER TABLE `cabang` DISABLE KEYS */;
INSERT INTO `cabang` VALUES (1,'PGA','TAP Kota Pagar Alam','Kota Pagar Alam','Kota Pagar Alam','Sumatera Selatan','08','pagaralam@sinartelkom.com','Hari Priyanto','active','2025-11-07 21:33:12','2025-11-08 05:10:19'),(2,'LHT','TAP Lahat','Lahat','Lahat','Sumatera Selatan','08','lahat@sinartelkom.com','Andrian Guntur','active','2025-11-07 21:33:12','2025-11-08 05:09:40'),(4,'MRS','TAP Musi Rawas','Musi Rawas','Musi Rawas','Sumatera Selatan','08','musirawas@sinartelkom.com','Perli','active','2025-11-07 21:33:12','2025-11-08 05:48:20'),(5,'LLG','TAP Kota Lubuklinggau','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','08','taplubuklinggau@sinartelkom.com','Wahyu Hidayat','active','2025-11-07 21:33:12','2025-11-08 05:07:55'),(17,'EMPL','TAP Empat Lawang (Tebing Tinggi)','Tebing Tinggi Empat Lawang','Empat Lawang','Sumatera Selatan','08','empatlawangtbg@sinartelkom.com','Doddy Delta','active','2025-11-08 05:11:27','2025-11-08 05:11:27'),(18,'PDP','TAP Empat Lawang (Pendopo)','Pendopo Empat Lawang','Empat Lawang','Sumatera Selatan','08','empatlawangpdp@sinartelkom.com','Jeni Handika','active','2025-11-08 05:12:08','2025-11-09 11:50:55'),(44,'WRHLLG','Warehouse Lubuklinggau','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','08','warehousllg@sinartelkom.com','Irfani','active','2025-11-08 10:10:02','2025-11-08 10:10:02'),(45,'WRHLHT','Warehouse Lahat','Lahat','Lahat','Sumatera Selatan','08','warehouslht@sinartelkom.com','Irfani','active','2025-11-08 10:10:39','2025-11-08 10:10:39'),(46,'OUTER','Out Cluster','Out Cluster New Musi Rawas','Semua Kota','Semua Provinsi','08','Outer@sinartelkom.com','Wirahadi Koesuma','active','2025-11-09 11:33:23','2025-11-09 11:33:23'),(47,'INNER','Player Inner','Seluruh City Cluster New Musi Rawas','Seluruh City Cluster New Musi Rawas','Sumatera Selatan','08','playerinner@sinartelkom.com','Wirahadi Koesuma','active','2025-11-09 11:34:29','2025-11-09 11:34:29'),(48,'MRSU','TAP Musi Rawas Utara','Musi Rawas Utara','Musi Rawas Utara','Sumatera Selatan','08','musirawasutara@sinartelkom.com','Ahmad Rifko','active','2025-11-09 11:52:02','2025-11-09 11:52:02');
/*!40000 ALTER TABLE `cabang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_penjualan`
--

DROP TABLE IF EXISTS `detail_penjualan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detail_penjualan` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `penjualan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `diskon` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`detail_id`),
  KEY `idx_penjualan_id` (`penjualan_id`),
  KEY `idx_produk_id` (`produk_id`),
  KEY `idx_detail_penjualan_produk` (`produk_id`,`penjualan_id`),
  CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualan` (`penjualan_id`) ON DELETE CASCADE,
  CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`produk_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_penjualan`
--

LOCK TABLES `detail_penjualan` WRITE;
/*!40000 ALTER TABLE `detail_penjualan` DISABLE KEYS */;
INSERT INTO `detail_penjualan` VALUES (6,6,34,'Voucher Fisik Segel Lite',500.00,3000,0.00,1500000.00,'2025-11-12 06:44:09'),(7,7,34,'Voucher Fisik Segel Lite',500.00,1000,0.00,500000.00,'2025-11-12 06:44:45'),(8,8,34,'Voucher Fisik Segel Lite',500.00,150,0.00,75000.00,'2025-11-12 06:49:58'),(9,9,34,'Voucher Fisik Segel Lite',500.00,200,0.00,100000.00,'2025-11-12 06:56:42'),(10,10,26,'Preload 3GB 30 Hari',28000.00,12,0.00,336000.00,'2025-11-12 10:31:47'),(11,10,34,'Voucher Fisik Segel Lite',500.00,500,0.00,250000.00,'2025-11-12 10:31:47');
/*!40000 ALTER TABLE `detail_penjualan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`inventory_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_produk_id` (`produk_id`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_tipe_transaksi` (`tipe_transaksi`),
  KEY `idx_inventory_produk_tanggal` (`produk_id`,`tanggal`),
  KEY `cabang_id` (`cabang_id`),
  KEY `idx_status_approval` (`status_approval`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`produk_id`),
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `inventory_ibfk_3` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_ibfk_4` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (6,34,44,'2025-11-12','masuk',275000,0,275000,'MASUK-20251112-3681','Stock Masuk - Alasan: DO Cluster','approved',9,'2025-11-12 06:42:28'),(7,34,45,'2025-11-12','masuk',275000,0,275000,'MASUK-20251112-4144','Stock Masuk - Alasan: DO Cluster','approved',9,'2025-11-12 06:42:38'),(8,34,45,'2025-11-12','keluar',3000,550000,547000,'INV-20251112-2905','Penjualan ke reseller: Irfani','approved',9,'2025-11-12 06:44:09'),(9,34,44,'2025-11-12','keluar',1000,547000,546000,'INV-20251112-1145','Penjualan ke reseller: Wirahadi Koesuma','approved',9,'2025-11-12 06:44:45'),(10,34,45,'2025-11-12','keluar',150,546000,545850,'INV-20251112-9174','Penjualan ke reseller: Irfani','approved',8,'2025-11-12 06:49:58'),(11,34,45,'2025-11-12','keluar',200,545850,545650,'INV-20251112-9136','Penjualan ke reseller: Irfani','approved',8,'2025-11-12 06:56:42'),(12,26,45,'2025-11-12','masuk',2000,0,2000,'MASUK-20251112-3526','Stock Masuk - Alasan: DO Cluster','approved',8,'2025-11-12 10:31:24'),(13,26,45,'2025-11-12','keluar',12,2000,1988,'INV-20251112-8401','Penjualan ke reseller: Irfani','approved',8,'2025-11-12 10:31:47'),(14,34,45,'2025-11-12','keluar',500,545650,545150,'INV-20251112-8401','Penjualan ke reseller: Irfani','approved',8,'2025-11-12 10:31:47');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori_produk`
--

DROP TABLE IF EXISTS `kategori_produk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori_produk` (
  `kategori_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT '?',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`kategori_id`),
  UNIQUE KEY `nama_kategori` (`nama_kategori`),
  KEY `idx_status` (`status`),
  KEY `idx_nama` (`nama_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori_produk`
--

LOCK TABLES `kategori_produk` WRITE;
/*!40000 ALTER TABLE `kategori_produk` DISABLE KEYS */;
INSERT INTO `kategori_produk` VALUES (6,'Voucher Internet Lite','Voucher Fisik Internet Lite','🔖','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(7,'Voucher Internet ByU','Voucher Internet ByU','🩹','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(8,'Perdana Internet Lite','Perdana Internet Lite','📕','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(9,'Perdana Internet ByU','Perdana Internet ByU','📘','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(10,'LinkAja','Saldo LinkAja','💴','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(11,'Finpay','Saldo Finpay','💶','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(12,'Voucher Segel Lite','Voucher Segel Lite','♦️','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(13,'Voucher Segel ByU','Voucher Segel ByU','♠️','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(14,'Perdana Segel Red 0K','Perdana Segel Red 0K','📕','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(15,'Perdana Segel ByU 0K','Perdana Segel ByU 0K','📘','active','2025-11-12 06:25:31','2025-11-12 06:25:31'),(16,'Perdana Internet Special','Perdana Internet Special (Nomor Cantik 260GB)','💎','active','2025-11-12 06:25:31','2025-11-12 06:25:31');
/*!40000 ALTER TABLE `kategori_produk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pelanggan`
--

DROP TABLE IF EXISTS `pelanggan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pelanggan` (
  `pelanggan_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`pelanggan_id`),
  UNIQUE KEY `kode_pelanggan` (`kode_pelanggan`),
  KEY `idx_kode_pelanggan` (`kode_pelanggan`),
  KEY `idx_phone` (`phone`),
  KEY `idx_tipe_pelanggan` (`tipe_pelanggan`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pelanggan`
--

LOCK TABLES `pelanggan` WRITE;
/*!40000 ALTER TABLE `pelanggan` DISABLE KEYS */;
INSERT INTO `pelanggan` VALUES (6,'CUST-34196','Irfani',NULL,'000000000',NULL,NULL,NULL,NULL,'corporate','active','2025-11-12 06:44:09','2025-11-12 06:44:09'),(7,'CUST-16335','Wirahadi Koesuma',NULL,'000000000',NULL,NULL,NULL,NULL,'corporate','active','2025-11-12 06:44:45','2025-11-12 06:44:45');
/*!40000 ALTER TABLE `pelanggan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penjualan`
--

DROP TABLE IF EXISTS `penjualan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `penjualan` (
  `penjualan_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`penjualan_id`),
  UNIQUE KEY `no_invoice` (`no_invoice`),
  KEY `idx_no_invoice` (`no_invoice`),
  KEY `idx_tanggal_penjualan` (`tanggal_penjualan`),
  KEY `idx_pelanggan_id` (`pelanggan_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status_pembayaran` (`status_pembayaran`),
  KEY `idx_status_pengiriman` (`status_pengiriman`),
  KEY `idx_penjualan_tanggal_status` (`tanggal_penjualan`,`status_pembayaran`),
  KEY `cabang_id` (`cabang_id`),
  KEY `reseller_id` (`reseller_id`),
  CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`pelanggan_id`),
  CONSTRAINT `penjualan_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `penjualan_ibfk_3` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  CONSTRAINT `penjualan_ibfk_4` FOREIGN KEY (`reseller_id`) REFERENCES `reseller` (`reseller_id`) ON DELETE SET NULL,
  CONSTRAINT `penjualan_ibfk_5` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  CONSTRAINT `penjualan_ibfk_6` FOREIGN KEY (`reseller_id`) REFERENCES `reseller` (`reseller_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penjualan`
--

LOCK TABLES `penjualan` WRITE;
/*!40000 ALTER TABLE `penjualan` DISABLE KEYS */;
INSERT INTO `penjualan` VALUES (6,'INV-20251112-2905','2025-11-12',6,9,45,39,1500000.00,0.00,0.00,1500000.00,'Transfer','Paid (Lunas)','pending',NULL,'2025-11-12 06:44:09','2025-11-12 06:44:09'),(7,'INV-20251112-1145','2025-11-12',7,9,44,37,500000.00,0.00,0.00,500000.00,'Transfer','Paid (Lunas)','pending',NULL,'2025-11-12 06:44:45','2025-11-12 06:44:45'),(8,'INV-20251112-9174','2025-11-12',6,8,45,39,75000.00,0.00,0.00,75000.00,'Budget Komitmen','TOP (Term Off Payment)','pending',NULL,'2025-11-12 06:49:58','2025-11-12 06:49:58'),(9,'INV-20251112-9136','2025-11-12',6,8,45,39,100000.00,0.00,0.00,100000.00,'Transfer','Paid (Lunas)','pending',NULL,'2025-11-12 06:56:42','2025-11-12 06:56:42'),(10,'INV-20251112-8401','2025-11-12',6,8,45,39,586000.00,0.00,0.00,586000.00,'Cash','Paid (Lunas)','pending',NULL,'2025-11-12 10:31:47','2025-11-12 10:31:47');
/*!40000 ALTER TABLE `penjualan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produk`
--

DROP TABLE IF EXISTS `produk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produk` (
  `produk_id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_produk` varchar(50) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(15,2) NOT NULL,
  `harga_promo` decimal(15,2) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `satuan` varchar(20) DEFAULT 'unit',
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`produk_id`),
  UNIQUE KEY `kode_produk` (`kode_produk`),
  KEY `idx_kode_produk` (`kode_produk`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_status` (`status`),
  KEY `cabang_id` (`cabang_id`),
  KEY `idx_kategori_id` (`kategori_id`),
  CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  CONSTRAINT `produk_ibfk_2` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produk`
--

LOCK TABLES `produk` WRITE;
/*!40000 ALTER TABLE `produk` DISABLE KEYS */;
INSERT INTO `produk` VALUES (11,'VF-Lite-Inet -001','VF Internet Lite 1,5GB 3Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 1,5GB 3Hari',8002.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(12,'VF-Lite-Inet -002','VF Internet Lite 2GB 3Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 2GB 3Hari',10120.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(13,'VF-Lite-Inet -003','VF Internet Lite 3GB 3Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 3GB 3Hari',11300.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(14,'VF-Lite-Inet -004','VF Internet Lite 1GB 1Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 1GB 1Hari',5500.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(15,'VF-Lite-Inet -005','VF Internet Lite 2GB 5Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 2GB 5Hari',11400.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(16,'VF-Lite-Inet -006','VF Internet Lite 2,5GB 5Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 2,5GB 5Hari',10700.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(17,'VF-Lite-Inet -007','VF Internet Lite 3GB 5Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 3GB 5Hari',12520.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(18,'VF-Lite-Inet -008','VF Internet Lite 5GB 5Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 5GB 5Hari',21300.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(19,'VF-Lite-Inet -009','VF Internet Lite 7GB 7Hari','Voucher Internet Lite',NULL,NULL,'VF Internet Lite 7GB 7Hari',25500.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(20,'VF-Lite-Inet -010','VF Internet ByU ByU 1GB 1Hari','Voucher Internet ByU',NULL,NULL,'VF Internet ByU ByU 1GB 1Hari',3500.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(21,'VF-Lite-Inet -011','VF Internet ByU ByU 2GB 1Hari','Voucher Internet ByU',NULL,NULL,'VF Internet ByU ByU 2GB 1Hari',6000.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(22,'VF-ByU-Inet -001','VF Internet ByU ByU 3GB 3Hari','Voucher Internet ByU',NULL,NULL,'VF Internet ByU ByU 3GB 3Hari',8500.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(23,'VF-ByU-Inet -002','VF Internet ByU ByU 4GB 7Hari','Voucher Internet ByU',NULL,NULL,'VF Internet ByU ByU 4GB 7Hari',10500.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(24,'VF-ByU-Inet -003','VF Internet ByU ByU 9GB 30Hari','Voucher Internet ByU',NULL,NULL,'VF Internet ByU ByU 9GB 30Hari',25500.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(25,'VF-ByU-Inet -004','VF Internet ByU ByU 7GB 30Hari','Voucher Internet ByU',NULL,NULL,'VF Internet ByU ByU 7GB 30Hari',15500.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(26,'SA-PRL-3GB','Preload 3GB 30 Hari','Perdana Internet Lite',NULL,NULL,'Preload 3GB 30 Hari',28000.00,NULL,1988,'unit','active','2025-11-12 06:37:31','2025-11-12 10:31:47'),(27,'SA-INET-3GB','Perdana Internet SA 3GB','Perdana Internet Lite',NULL,NULL,'Perdana Internet SA 3GB',28000.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(28,'SA-BYU-3GB','SA ByU 3GB 30D','Perdana Internet ByU',NULL,NULL,'SA ByU 3GB 30D',28000.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(29,'SA-BYU-3GB-A','SA ByU 3GB 30D (Alladin)','Perdana Internet ByU',NULL,NULL,'SA ByU 3GB 30D (Alladin)',15000.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(30,'Link','Saldo LinkAja','LinkAja',NULL,NULL,'Saldo LinkAja',1.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(31,'Finpay','Saldo Finpay','Finpay',NULL,NULL,'Saldo Finpay',1.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(32,'RED-1','Perdana Segel Red 0K','Perdana Segel Red 0K',NULL,NULL,'Perdana Segel Red 0K',10000.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(33,'BYU-1','Perdana Segel ByU 0K','Perdana Segel ByU 0K',NULL,NULL,'Perdana Segel ByU 0K',10000.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31'),(34,'VF-Lite','Voucher Fisik Segel Lite','Voucher Segel Lite',NULL,NULL,'Voucher Fisik Segel Lite',500.00,NULL,545150,'unit','active','2025-11-12 06:37:31','2025-11-12 10:31:47'),(35,'VF-ByU','Voucher Fisik Segel ByU','Voucher Segel ByU',NULL,NULL,'Voucher Fisik Segel ByU',500.00,NULL,0,'unit','active','2025-11-12 06:37:31','2025-11-12 06:37:31');
/*!40000 ALTER TABLE `produk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reseller`
--

DROP TABLE IF EXISTS `reseller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reseller` (
  `reseller_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `kategori` enum('Sales Force','General Manager','Manager','Supervisor','Player/Pemain','Merchant') DEFAULT NULL,
  PRIMARY KEY (`reseller_id`),
  UNIQUE KEY `kode_reseller` (`kode_reseller`),
  KEY `cabang_id` (`cabang_id`),
  CONSTRAINT `reseller_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reseller`
--

LOCK TABLES `reseller` WRITE;
/*!40000 ALTER TABLE `reseller` DISABLE KEYS */;
INSERT INTO `reseller` VALUES (1,'TPT005','Andrian Guntur','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','8','supervisor@sinartelkom.com','Andrian Guntur',2,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:19:03','Supervisor'),(2,'TPT006','Wahyu Hidayat','CV. SINAR TELEKOM','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','8','supervisor@sinartelkom.com','Wahyu Hidayat',5,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:18:58','Supervisor'),(3,'TPT007','Perli','CV. SINAR TELEKOM','Musi Rawas','Musi Rawas','Sumatera Selatan','8','supervisor@sinartelkom.com','Perli',4,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:18:51','Supervisor'),(4,'TPT008','Dody Delta','CV. SINAR TELEKOM','Empat Lawang','Empat Lawang','Sumatera Selatan','8','supervisor@sinartelkom.com','Dody Delta',17,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:18:46','Supervisor'),(5,'TPT009','Ahmad Rifko','CV. SINAR TELEKOM','Musi Rawas Utara','Musi Rawas Utara','Sumatera Selatan','8','supervisor@sinartelkom.com','Ahmad Rifko',48,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:18:39','Supervisor'),(6,'TPT010','Hari Priyanto','CV. SINAR TELEKOM','Kota Pagar Alam','Kota Pagar Alam','Sumatera Selatan','8','supervisor@sinartelkom.com','Hari Priyanto',1,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:18:30','Supervisor'),(7,'TPT011','Jeni Handika','CV. SINAR TELEKOM','Empat Lawang','Empat Lawang','Sumatera Selatan','8','supervisor@sinartelkom.com','Jeni Handika',18,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:18:22','Supervisor'),(8,'TPT012','Donal Septian','CV. SINAR TELEKOM','Empat Lawang','Empat Lawang','Sumatera Selatan','8','salesforce@sinartelkom.com','Donal Septian',18,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:18:13','Sales Force'),(9,'TPT013','Ego Saputra','CV. SINAR TELEKOM','Empat Lawang','Empat Lawang','Sumatera Selatan','8','salesforce@sinartelkom.com','Ego Saputra',18,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:18:06','Sales Force'),(10,'TPT014','Kgs Jauhari Fikri','CV. SINAR TELEKOM','Empat Lawang','Empat Lawang','Sumatera Selatan','8','salesforce@sinartelkom.com','Kgs Jauhari Fikri',17,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:57','Sales Force'),(11,'TPT015','Angga Ihza Rambe','CV. SINAR TELEKOM','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','8','salesforce@sinartelkom.com','Angga Ihza Rambe',5,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:50','Sales Force'),(12,'TPT016','Angga Triska','CV. SINAR TELEKOM','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','8','salesforce@sinartelkom.com','Angga Triska',5,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:39','Sales Force'),(13,'TPT017','Derry Setyadi','CV. SINAR TELEKOM','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','8','salesforce@sinartelkom.com','Derry Setyadi',5,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:34','Sales Force'),(14,'TPT018','Jaka Umbara','CV. SINAR TELEKOM','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','8','salesforce@sinartelkom.com','Jaka Umbara',5,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:29','Sales Force'),(15,'TPT019','Leo Waldi','CV. SINAR TELEKOM','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','8','salesforce@sinartelkom.com','Leo Waldi',5,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:24','Sales Force'),(16,'TPT020','Frediansyah','CV. SINAR TELEKOM','Kota Pagar Alam','Kota Pagar Alam','Sumatera Selatan','8','salesforce@sinartelkom.com','Frediansyah',1,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:19','Sales Force'),(17,'TPT021','Ivan Gustiawan','CV. SINAR TELEKOM','Kota Pagar Alam','Kota Pagar Alam','Sumatera Selatan','8','salesforce@sinartelkom.com','Ivan Gustiawan',1,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:15','Sales Force'),(18,'TPT022','Nurul Herizen','CV. SINAR TELEKOM','Kota Pagar Alam','Kota Pagar Alam','Sumatera Selatan','8','salesforce@sinartelkom.com','Nurul Herizen',1,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:10','Sales Force'),(19,'TPT023','Alpi Syahrin','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','8','salesforce@sinartelkom.com','Alpi Syahrin',2,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:17:03','Sales Force'),(20,'TPT024','Dani Rahman','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','8','salesforce@sinartelkom.com','Dani Rahman',2,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:58','Sales Force'),(21,'TPT025','Jumadil Qubro','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','8','salesforce@sinartelkom.com','Jumadil Qubro',2,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:53','Sales Force'),(22,'TPT026','Naufal Daffa Faros','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','8','salesforce@sinartelkom.com','Naufal Daffa Faros',2,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:49','Sales Force'),(23,'TPT027','Rian Satria Agung Saputra','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','8','salesforce@sinartelkom.com','Rian Satria Agung Saputra',2,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:44','Sales Force'),(24,'TPT028','Sonny Tonando','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','8','salesforce@sinartelkom.com','Sonny Tonando',2,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:40','Sales Force'),(25,'TPT029','Agung Maulana','CV. SINAR TELEKOM','Musi Rawas','Musi Rawas','Sumatera Selatan','8','salesforce@sinartelkom.com','Agung Maulana',4,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:36','Sales Force'),(26,'TPT030','Agus Waluyo','CV. SINAR TELEKOM','Musi Rawas','Musi Rawas','Sumatera Selatan','8','salesforce@sinartelkom.com','Agus Waluyo',4,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:31','Sales Force'),(27,'TPT031','Dopri Zuarsyah','CV. SINAR TELEKOM','Musi Rawas','Musi Rawas','Sumatera Selatan','8','salesforce@sinartelkom.com','Dopri Zuarsyah',4,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:25','Sales Force'),(28,'TPT032','Jeni Handika','CV. SINAR TELEKOM','Musi Rawas','Musi Rawas','Sumatera Selatan','8','salesforce@sinartelkom.com','Jeni Handika',4,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:20','Sales Force'),(29,'TPT033','Sholahuddin','CV. SINAR TELEKOM','Musi Rawas','Musi Rawas','Sumatera Selatan','8','salesforce@sinartelkom.com','Sholahuddin',4,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:16','Sales Force'),(30,'TPT034','Triono Susanto','CV. SINAR TELEKOM','Musi Rawas','Musi Rawas','Sumatera Selatan','8','salesforce@sinartelkom.com','Triono Susanto',4,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:11','Sales Force'),(31,'TPT035','Abdul Halim','CV. SINAR TELEKOM','Musi Rawas Utara','Musi Rawas Utara','Sumatera Selatan','8','salesforce@sinartelkom.com','Abdul Halim',48,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:16:00','Sales Force'),(32,'TPT036','Angga Pranata','CV. SINAR TELEKOM','Musi Rawas Utara','Musi Rawas Utara','Sumatera Selatan','8','salesforce@sinartelkom.com','Angga Pranata',48,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:15:54','Sales Force'),(33,'TPT037','David Alex Sander','CV. SINAR TELEKOM','Musi Rawas Utara','Musi Rawas Utara','Sumatera Selatan','8','salesforce@sinartelkom.com','David Alex Sander',48,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:15:47','Sales Force'),(34,'TPT038','Deni Farizal','CV. SINAR TELEKOM','Musi Rawas Utara','Musi Rawas Utara','Sumatera Selatan','8','salesforce@sinartelkom.com','Deni Farizal',48,'active',0.00,'2025-11-11 06:12:19','2025-11-11 06:15:40','Sales Force'),(35,'PLYR001','SEDAYU','SEDAYU','Musi Rawas','Musi Rawas','Sumatera Selatan','08','sedayu@sinartelkom.com','Ahmad Wahyudi',47,'active',0.00,'2025-11-11 06:41:55','2025-11-11 06:41:55','Player/Pemain'),(36,'TPT001','Wirahadi Koesuma','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','08','wirahadi@sinartelkom.com','Pak Wira',45,'active',0.00,'2025-11-11 06:44:38','2025-11-11 06:44:38','General Manager'),(37,'TPT002','Wirahadi Koesuma','CV. SINAR TELEKOM','Lahat','Lahat','Sumatera Selatan','08','wirahadi@sinartelkom.com','Pak Wira',44,'active',0.00,'2025-11-11 06:45:09','2025-11-11 06:45:09','General Manager'),(38,'TPT003','Irfani','CV. SINAR TELEKOM','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','08','irfani@sinartelkom.com','Bang Irfan',44,'active',0.00,'2025-11-11 06:45:53','2025-11-11 06:45:53','Manager'),(39,'TPT004','Irfani','CV. SINAR TELEKOM','Kota Lubuklinggau','Kota Lubuklinggau','Sumatera Selatan','08','irfani@sinartelkom.com','Bang Irfan',45,'active',0.00,'2025-11-11 06:46:16','2025-11-11 06:46:16','Manager');
/*!40000 ALTER TABLE `reseller` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setoran_evidence`
--

DROP TABLE IF EXISTS `setoran_evidence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setoran_evidence` (
  `evidence_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evidence_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_cabang` (`cabang`),
  KEY `idx_bank` (`bank`),
  KEY `idx_setoran_evidence_nominal` (`nominal`),
  KEY `idx_setoran_evidence_bank_pengirim` (`bank_pengirim`),
  CONSTRAINT `setoran_evidence_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setoran_evidence`
--

LOCK TABLES `setoran_evidence` WRITE;
/*!40000 ALTER TABLE `setoran_evidence` DISABLE KEYS */;
INSERT INTO `setoran_evidence` VALUES (1,'2025-11-12','Warehouse Lubuklinggau','Irfani','BCA','BCA CV',1650000.00,'/sinartelekomdashboardsystem/assets/images/evidence/evidence_20251112_074545_9_31a18445.png','',9,'2025-11-12 06:45:45','2025-11-12 06:45:45'),(2,'2025-11-12','Warehouse Lubuklinggau','Wirahadi Koesuma','MANDIRI','MANDIRI CV',500000.00,'/sinartelekomdashboardsystem/assets/images/evidence/evidence_20251112_074609_9_7523f548.png','',9,'2025-11-12 06:46:09','2025-11-12 06:46:09'),(3,'2025-11-12','Warehouse Lahat','Irfani','BCA','BCA CV',2235000.00,'/sinartelekomdashboardsystem/assets/images/evidence/evidence_20251112_113756_8_e58c17d9.png','',8,'2025-11-12 10:37:56','2025-11-12 10:37:56');
/*!40000 ALTER TABLE `setoran_evidence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setoran_harian`
--

DROP TABLE IF EXISTS `setoran_harian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setoran_harian` (
  `setoran_id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `cabang` varchar(100) DEFAULT NULL,
  `reseller` varchar(100) DEFAULT NULL,
  `produk` varchar(200) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `total_setoran` decimal(15,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setoran_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_cabang` (`cabang`),
  KEY `idx_reseller` (`reseller`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_tanggal_cabang` (`tanggal`,`cabang`),
  KEY `idx_tanggal_reseller` (`tanggal`,`reseller`),
  CONSTRAINT `setoran_harian_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setoran_harian`
--

LOCK TABLES `setoran_harian` WRITE;
/*!40000 ALTER TABLE `setoran_harian` DISABLE KEYS */;
INSERT INTO `setoran_harian` VALUES (1,'2025-11-12','Warehouse Lubuklinggau','Irfani','Voucher Fisik Segel Lite',3000,500.00,1500000.00,9,'2025-11-12 06:44:58','2025-11-12 06:44:58'),(2,'2025-11-12','Warehouse Lubuklinggau','Wirahadi Koesuma','Voucher Fisik Segel Lite',1000,500.00,500000.00,9,'2025-11-12 06:44:58','2025-11-12 06:44:58'),(3,'2025-11-12','Warehouse Lahat','Irfani','Voucher Fisik Segel Lite',3000,500.00,1500000.00,8,'2025-11-12 10:37:37','2025-11-12 10:37:37'),(4,'2025-11-12','Warehouse Lahat','Irfani','Voucher Fisik Segel Lite',150,500.00,75000.00,8,'2025-11-12 10:37:37','2025-11-12 10:37:37'),(5,'2025-11-12','Warehouse Lahat','Irfani','Voucher Fisik Segel Lite',200,500.00,100000.00,8,'2025-11-12 10:37:37','2025-11-12 10:37:37'),(6,'2025-11-12','Warehouse Lahat','Irfani','Preload 3GB 30 Hari',12,28000.00,336000.00,8,'2025-11-12 10:37:37','2025-11-12 10:37:37'),(7,'2025-11-12','Warehouse Lahat','Irfani','Voucher Fisik Segel Lite',500,500.00,250000.00,8,'2025-11-12 10:37:37','2025-11-12 10:37:37');
/*!40000 ALTER TABLE `setoran_harian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `cabang_id` (`cabang_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`cabang_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (6,'admin_pagaralam','$2y$10$.p5mj4Uvg4O7HOVlR3sztuQVRuDmNOz5zf49FMOGrGUULfaA1sC1.','Admin TAP Pagar Alam','adminpagaralam@sinartelkom.com','08','staff',1,'active','2025-11-08 09:26:52','2025-11-08 16:29:44','2025-11-08 16:29:44'),(7,'julita_novita','$2y$10$vcGw07VhUGCFo2SCBUrbt.aXZ1QE4bSKpuKNy7WeivGpdE6dZSE7e','Julita Novita Sari','julitanovitasari@sinartelkom.com','08','finance',44,'active','2025-11-08 10:11:21','2025-11-10 08:26:56','2025-11-08 15:18:44'),(8,'ayu_tiara','$2y$10$RIr77SdYq26H2jo2gWZRWuGUZ14IOONId/kzpHmVZqgbvfWZzZOdC','Ayu Tiara Veronica','ayutiaraveronica@sinartelkom.com','08','finance',45,'active','2025-11-08 10:15:10','2025-11-12 09:07:23','2025-11-12 09:07:23'),(9,'rhudychandra','$2y$10$VGpBhlnehkWynHoaLxsDsOqS6R1UhHKuj8JA.5sL/P4jv3Q8ZIZwG','Rhudy Chandra','rhudychandra@sinartelkom.com','081234567890','administrator',NULL,'active','2025-11-12 06:31:22','2025-11-12 06:51:10','2025-11-12 06:51:10'),(10,'andrianguntur','$2y$10$6KcjPtLhBWVr.ESkkhp5A.7G.FmQIODjLVsXc0WBFBv8CSCRacl3e','Andrian Guntur','andrianguntur@sinartelkom.com','08','supervisor',2,'active','2025-11-08 16:48:40','2025-11-10 08:27:10',NULL),(11,'shela_damayanti','$2y$10$GM55DgepWnwCE4E2oe9LaOgaOpFsbNmzuCis/wKWuXNuXxjk6O.Wq','Shela Damayanti','sheladamayanti@sinartelkom.com','08','staff',4,'active','2025-11-09 09:27:12','2025-11-09 12:03:53','2025-11-09 12:03:53'),(12,'dwifitria_wulandari','$2y$10$YaFOI/y9fbBh3Ax.SNpv4OcVFRSBmx5C62RnTLm5Pr3e4WtpPda7q','Dwi Fitria Wualndari','dwifitriawulandari@sinartelkom.com','08','staff',5,'active','2025-11-09 15:04:35','2025-11-09 15:04:35',NULL),(13,'mentari_dwi','$2y$10$HNBBx3WqfwvU55rMlNlsTOX41dlAW/JCXDvc9oG4EoHwU1AfAHDwu','Mentari Dwi Satwika','mentaridwi@sinartelkom.com','08','staff',48,'active','2025-11-09 15:07:15','2025-11-09 15:07:15',NULL),(14,'hera_fitriani','$2y$10$FTfg3S3pZAtUKO42JxQcYONXPKaHDwZSKsgbjn.baxzy2Km6vyyXW','Hera Fitriani','herafitriani@sinartelkom.com','08','finance',44,'active','2025-11-11 06:47:11','2025-11-11 06:47:11',NULL),(15,'irfani','$2y$10$gbJLrbP1erbIRe1GmeCpTOsoJ25OTW7C37UFI/0atvsi/M5JwUz5e','Irfani','irfani@sinartelkom.com','08','manager',NULL,'active','2025-11-08 14:09:32','2025-11-12 06:41:30','2025-11-11 19:17:28');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `view_admin_dashboard`
--

DROP TABLE IF EXISTS `view_admin_dashboard`;
/*!50001 DROP VIEW IF EXISTS `view_admin_dashboard`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_admin_dashboard` AS SELECT
 1 AS `total_cabang`,
  1 AS `total_reseller`,
  1 AS `total_users`,
  1 AS `total_produk`,
  1 AS `total_penjualan`,
  1 AS `total_stok` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `view_laporan_penjualan`
--

DROP TABLE IF EXISTS `view_laporan_penjualan`;
/*!50001 DROP VIEW IF EXISTS `view_laporan_penjualan`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_laporan_penjualan` AS SELECT
 1 AS `penjualan_id`,
  1 AS `no_invoice`,
  1 AS `tanggal_penjualan`,
  1 AS `kode_pelanggan`,
  1 AS `nama_pelanggan`,
  1 AS `sales_person`,
  1 AS `subtotal`,
  1 AS `diskon`,
  1 AS `pajak`,
  1 AS `total`,
  1 AS `metode_pembayaran`,
  1 AS `status_pembayaran`,
  1 AS `status_pengiriman`,
  1 AS `created_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `view_reseller_performance`
--

DROP TABLE IF EXISTS `view_reseller_performance`;
/*!50001 DROP VIEW IF EXISTS `view_reseller_performance`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_reseller_performance` AS SELECT
 1 AS `reseller_id`,
  1 AS `kode_reseller`,
  1 AS `nama_reseller`,
  1 AS `nama_perusahaan`,
  1 AS `nama_cabang`,
  1 AS `total_transaksi`,
  1 AS `total_pembelian`,
  1 AS `status` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `view_sales_per_cabang`
--

DROP TABLE IF EXISTS `view_sales_per_cabang`;
/*!50001 DROP VIEW IF EXISTS `view_sales_per_cabang`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_sales_per_cabang` AS SELECT
 1 AS `cabang_id`,
  1 AS `kode_cabang`,
  1 AS `nama_cabang`,
  1 AS `kota`,
  1 AS `total_transaksi`,
  1 AS `total_penjualan`,
  1 AS `jumlah_reseller` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `view_sales_performance`
--

DROP TABLE IF EXISTS `view_sales_performance`;
/*!50001 DROP VIEW IF EXISTS `view_sales_performance`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_sales_performance` AS SELECT
 1 AS `user_id`,
  1 AS `username`,
  1 AS `full_name`,
  1 AS `role`,
  1 AS `total_transaksi`,
  1 AS `total_penjualan`,
  1 AS `rata_rata_transaksi`,
  1 AS `transaksi_terbesar` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `view_stock_per_cabang`
--

DROP TABLE IF EXISTS `view_stock_per_cabang`;
/*!50001 DROP VIEW IF EXISTS `view_stock_per_cabang`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_stock_per_cabang` AS SELECT
 1 AS `cabang_id`,
  1 AS `kode_cabang`,
  1 AS `nama_cabang`,
  1 AS `jumlah_produk`,
  1 AS `total_stok`,
  1 AS `nilai_stok` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `view_stok_produk`
--

DROP TABLE IF EXISTS `view_stok_produk`;
/*!50001 DROP VIEW IF EXISTS `view_stok_produk`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_stok_produk` AS SELECT
 1 AS `produk_id`,
  1 AS `kode_produk`,
  1 AS `nama_produk`,
  1 AS `kategori`,
  1 AS `harga`,
  1 AS `harga_promo`,
  1 AS `stok`,
  1 AS `status`,
  1 AS `status_stok` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `view_top_selling_products`
--

DROP TABLE IF EXISTS `view_top_selling_products`;
/*!50001 DROP VIEW IF EXISTS `view_top_selling_products`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_top_selling_products` AS SELECT
 1 AS `produk_id`,
  1 AS `kode_produk`,
  1 AS `nama_produk`,
  1 AS `kategori`,
  1 AS `jumlah_transaksi`,
  1 AS `total_terjual`,
  1 AS `total_revenue` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `view_admin_dashboard`
--

/*!50001 DROP VIEW IF EXISTS `view_admin_dashboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_admin_dashboard` AS select (select count(0) from `cabang` where `cabang`.`status` = 'active') AS `total_cabang`,(select count(0) from `reseller` where `reseller`.`status` = 'active') AS `total_reseller`,(select count(0) from `users` where `users`.`status` = 'active') AS `total_users`,(select count(0) from `produk`) AS `total_produk`,(select coalesce(sum(`penjualan`.`total`),0) from `penjualan` where `penjualan`.`status_pembayaran` = 'paid') AS `total_penjualan`,(select coalesce(sum(`produk`.`stok`),0) from `produk`) AS `total_stok` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_laporan_penjualan`
--

/*!50001 DROP VIEW IF EXISTS `view_laporan_penjualan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_laporan_penjualan` AS select `p`.`penjualan_id` AS `penjualan_id`,`p`.`no_invoice` AS `no_invoice`,`p`.`tanggal_penjualan` AS `tanggal_penjualan`,`pel`.`kode_pelanggan` AS `kode_pelanggan`,`pel`.`nama_pelanggan` AS `nama_pelanggan`,`u`.`full_name` AS `sales_person`,`p`.`subtotal` AS `subtotal`,`p`.`diskon` AS `diskon`,`p`.`pajak` AS `pajak`,`p`.`total` AS `total`,`p`.`metode_pembayaran` AS `metode_pembayaran`,`p`.`status_pembayaran` AS `status_pembayaran`,`p`.`status_pengiriman` AS `status_pengiriman`,`p`.`created_at` AS `created_at` from ((`penjualan` `p` join `pelanggan` `pel` on(`p`.`pelanggan_id` = `pel`.`pelanggan_id`)) join `users` `u` on(`p`.`user_id` = `u`.`user_id`)) order by `p`.`tanggal_penjualan` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_reseller_performance`
--

/*!50001 DROP VIEW IF EXISTS `view_reseller_performance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_reseller_performance` AS select `r`.`reseller_id` AS `reseller_id`,`r`.`kode_reseller` AS `kode_reseller`,`r`.`nama_reseller` AS `nama_reseller`,`r`.`nama_perusahaan` AS `nama_perusahaan`,`c`.`nama_cabang` AS `nama_cabang`,count(`p`.`penjualan_id`) AS `total_transaksi`,coalesce(sum(`p`.`total`),0) AS `total_pembelian`,`r`.`status` AS `status` from ((`reseller` `r` left join `cabang` `c` on(`r`.`cabang_id` = `c`.`cabang_id`)) left join `penjualan` `p` on(`r`.`reseller_id` = `p`.`reseller_id` and `p`.`status_pembayaran` = 'paid')) group by `r`.`reseller_id`,`r`.`kode_reseller`,`r`.`nama_reseller`,`r`.`nama_perusahaan`,`c`.`nama_cabang`,`r`.`status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_sales_per_cabang`
--

/*!50001 DROP VIEW IF EXISTS `view_sales_per_cabang`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_sales_per_cabang` AS select `c`.`cabang_id` AS `cabang_id`,`c`.`kode_cabang` AS `kode_cabang`,`c`.`nama_cabang` AS `nama_cabang`,`c`.`kota` AS `kota`,count(distinct `p`.`penjualan_id`) AS `total_transaksi`,coalesce(sum(`p`.`total`),0) AS `total_penjualan`,count(distinct `r`.`reseller_id`) AS `jumlah_reseller` from ((`cabang` `c` left join `penjualan` `p` on(`c`.`cabang_id` = `p`.`cabang_id` and `p`.`status_pembayaran` = 'paid')) left join `reseller` `r` on(`c`.`cabang_id` = `r`.`cabang_id` and `r`.`status` = 'active')) group by `c`.`cabang_id`,`c`.`kode_cabang`,`c`.`nama_cabang`,`c`.`kota` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_sales_performance`
--

/*!50001 DROP VIEW IF EXISTS `view_sales_performance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_sales_performance` AS select `u`.`user_id` AS `user_id`,`u`.`username` AS `username`,`u`.`full_name` AS `full_name`,`u`.`role` AS `role`,count(`p`.`penjualan_id`) AS `total_transaksi`,sum(`p`.`total`) AS `total_penjualan`,avg(`p`.`total`) AS `rata_rata_transaksi`,max(`p`.`total`) AS `transaksi_terbesar` from (`users` `u` left join `penjualan` `p` on(`u`.`user_id` = `p`.`user_id` and `p`.`status_pembayaran` = 'paid')) where `u`.`role` in ('sales','manager') group by `u`.`user_id`,`u`.`username`,`u`.`full_name`,`u`.`role` order by sum(`p`.`total`) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_stock_per_cabang`
--

/*!50001 DROP VIEW IF EXISTS `view_stock_per_cabang`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_stock_per_cabang` AS select `c`.`cabang_id` AS `cabang_id`,`c`.`kode_cabang` AS `kode_cabang`,`c`.`nama_cabang` AS `nama_cabang`,count(distinct `pr`.`produk_id`) AS `jumlah_produk`,coalesce(sum(`pr`.`stok`),0) AS `total_stok`,coalesce(sum(`pr`.`stok` * `pr`.`harga`),0) AS `nilai_stok` from (`cabang` `c` left join `produk` `pr` on(`c`.`cabang_id` = `pr`.`cabang_id`)) group by `c`.`cabang_id`,`c`.`kode_cabang`,`c`.`nama_cabang` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_stok_produk`
--

/*!50001 DROP VIEW IF EXISTS `view_stok_produk`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_stok_produk` AS select `pr`.`produk_id` AS `produk_id`,`pr`.`kode_produk` AS `kode_produk`,`pr`.`nama_produk` AS `nama_produk`,`pr`.`kategori` AS `kategori`,`pr`.`harga` AS `harga`,`pr`.`harga_promo` AS `harga_promo`,`pr`.`stok` AS `stok`,`pr`.`status` AS `status`,case when `pr`.`stok` <= 10 then 'Low Stock' when `pr`.`stok` <= 50 then 'Medium Stock' else 'High Stock' end AS `status_stok` from `produk` `pr` where `pr`.`status` = 'active' order by `pr`.`stok` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_top_selling_products`
--

/*!50001 DROP VIEW IF EXISTS `view_top_selling_products`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_top_selling_products` AS select `pr`.`produk_id` AS `produk_id`,`pr`.`kode_produk` AS `kode_produk`,`pr`.`nama_produk` AS `nama_produk`,`pr`.`kategori` AS `kategori`,count(`dp`.`detail_id`) AS `jumlah_transaksi`,sum(`dp`.`jumlah`) AS `total_terjual`,sum(`dp`.`subtotal`) AS `total_revenue` from ((`produk` `pr` join `detail_penjualan` `dp` on(`pr`.`produk_id` = `dp`.`produk_id`)) join `penjualan` `p` on(`dp`.`penjualan_id` = `p`.`penjualan_id`)) where `p`.`status_pembayaran` = 'paid' group by `pr`.`produk_id`,`pr`.`kode_produk`,`pr`.`nama_produk`,`pr`.`kategori` order by sum(`dp`.`subtotal`) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-12 17:44:17
