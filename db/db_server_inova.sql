-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: db_sitangkal
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `pemakaian_bbm`
--

DROP TABLE IF EXISTS `pemakaian_bbm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pemakaian_bbm` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `terima_dari` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penerima` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keperluan` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jumlah_kupon` int DEFAULT NULL,
  `nominal_kupon` decimal(14,2) DEFAULT NULL,
  `jenis_bbm` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jumlah_liter` decimal(10,2) DEFAULT NULL,
  `nominal_rupiah` decimal(14,2) NOT NULL DEFAULT '0.00',
  `kendaraan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `petugas_id` int DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `bukti` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'foto struk/kupon',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pemakaian_bbm`
--

LOCK TABLES `pemakaian_bbm` WRITE;
/*!40000 ALTER TABLE `pemakaian_bbm` DISABLE KEYS */;
INSERT INTO `pemakaian_bbm` VALUES (3,'2026-08-14','SPBU','Dadang','test',1,250000.00,'Pertamax',NULL,0.00,'D 34 AH',5,'test',NULL,'2026-08-14 07:51:11');
/*!40000 ALTER TABLE `pemakaian_bbm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pohon_histori`
--

DROP TABLE IF EXISTS `pohon_histori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pohon_histori` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pohon_id` int NOT NULL,
  `kondisi_kesehatan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `umur_pohon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `snapshot_json` json DEFAULT NULL COMMENT 'salinan lengkap baris pohon sebelum diubah',
  `diubah_oleh` int DEFAULT NULL,
  `diubah_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_histori_pohon` (`pohon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pohon_histori`
--

LOCK TABLES `pohon_histori` WRITE;
/*!40000 ALTER TABLE `pohon_histori` DISABLE KEYS */;
/*!40000 ALTER TABLE `pohon_histori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `laporan_penanaman`
--

DROP TABLE IF EXISTS `laporan_penanaman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `laporan_penanaman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `lokasi` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `asal_bibit` varchar(150) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'mis. Stok Bibit Dinas / Bantuan / Swadaya / CSR',
  `jenis_tanaman` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jumlah_bibit` int NOT NULL DEFAULT '0',
  `keterangan` text COLLATE utf8mb4_general_ci,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `petugas_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `laporan_penanaman`
--

LOCK TABLES `laporan_penanaman` WRITE;
/*!40000 ALTER TABLE `laporan_penanaman` DISABLE KEYS */;
/*!40000 ALTER TABLE `laporan_penanaman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pergantian_pohon`
--

DROP TABLE IF EXISTS `pergantian_pohon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pergantian_pohon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pengajuan_id` int DEFAULT NULL COMMENT 'terhubung ke pengajuan pemangkasan/penebangan jika ada',
  `pohon_id` int DEFAULT NULL,
  `jenis_pohon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `diameter_cm` decimal(6,2) NOT NULL,
  `jumlah_pohon` int NOT NULL DEFAULT '1',
  `tarif_id` int DEFAULT NULL,
  `harga_per_cm` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_biaya` decimal(14,2) NOT NULL DEFAULT '0.00',
  `nomor_surat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_surat` date DEFAULT NULL,
  `nama_kabid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nip_kabid` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dibuat_oleh` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pergantian_pohon`
--

LOCK TABLES `pergantian_pohon` WRITE;
/*!40000 ALTER TABLE `pergantian_pohon` DISABLE KEYS */;
INSERT INTO `pergantian_pohon` VALUES (2,NULL,NULL,'Umum / Tidak Diketahui',0.40,1,1,50000.00,20000.00,'123123','2026-08-14','test','21312',5,'2026-08-14 07:59:49');
/*!40000 ALTER TABLE `pergantian_pohon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penyajian_data`
--

DROP TABLE IF EXISTS `penyajian_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penyajian_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis` enum('iktl','rth_persen') COLLATE utf8mb4_general_ci NOT NULL,
  `tahun` year NOT NULL,
  `nilai` decimal(10,4) NOT NULL COMMENT 'Nilai IKTL, atau persentase RTH (0-100)',
  `keterangan` text COLLATE utf8mb4_general_ci,
  `dibuat_oleh` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jenis_tahun` (`jenis`,`tahun`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penyajian_data`
--

LOCK TABLES `penyajian_data` WRITE;
/*!40000 ALTER TABLE `penyajian_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `penyajian_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `potensi_penanaman`
--

DROP TABLE IF EXISTS `potensi_penanaman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `potensi_penanaman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipe` enum('realisasi','potensi') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'potensi',
  `nama_lokasi` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `kecamatan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `geometry` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT 'GeoJSON Point atau Polygon',
  `luas_m2` decimal(16,2) DEFAULT NULL COMMENT 'diisi jika geometry berupa polygon',
  `estimasi_jumlah_pohon` int DEFAULT NULL,
  `sumber_kajian` varchar(150) COLLATE utf8mb4_general_ci DEFAULT 'Kajian Potensi Penanaman Kota Cimahi 2026 (Anggaran Perubahan)',
  `keterangan` text COLLATE utf8mb4_general_ci,
  `dibuat_oleh` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `potensi_penanaman`
--

LOCK TABLES `potensi_penanaman` WRITE;
/*!40000 ALTER TABLE `potensi_penanaman` DISABLE KEYS */;
/*!40000 ALTER TABLE `potensi_penanaman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permintaan_sarpras`
--

DROP TABLE IF EXISTS `permintaan_sarpras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permintaan_sarpras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `terima_dari` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penerima` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_barang` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `satuan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unit',
  `alasan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('diajukan','disetujui','ditolak','selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'diajukan',
  `catatan_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `pemohon_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permintaan_sarpras`
--

LOCK TABLES `permintaan_sarpras` WRITE;
/*!40000 ALTER TABLE `permintaan_sarpras` DISABLE KEYS */;
INSERT INTO `permintaan_sarpras` VALUES (3,'2026-08-14','Gudang Dinas','Dadang','Testing',1,'unit','testing','diajukan',NULL,5,'2026-08-14 07:50:26');
/*!40000 ALTER TABLE `permintaan_sarpras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_menu_access`
--

DROP TABLE IF EXISTS `role_menu_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_menu_access` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_create` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_menu` (`role_id`,`menu_id`),
  KEY `fk_rma_role` (`role_id`),
  KEY `fk_rma_menu` (`menu_id`),
  CONSTRAINT `fk_rma_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rma_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_menu_access`
--

LOCK TABLES `role_menu_access` WRITE;
/*!40000 ALTER TABLE `role_menu_access` DISABLE KEYS */;
INSERT INTO `role_menu_access` VALUES (1,1,1,1,1,1,1),(2,1,7,1,1,1,1),(3,1,6,1,1,1,1),(4,1,2,1,1,1,1),(5,1,3,1,1,1,1),(6,1,4,1,1,1,1),(7,1,9,1,1,1,1),(8,1,5,1,1,1,1),(9,1,8,1,1,1,1),(16,2,1,1,0,0,0),(17,2,7,1,0,0,0),(18,2,6,1,0,0,0),(19,2,2,1,0,0,0),(20,2,3,1,0,0,0),(21,2,4,1,0,0,0),(22,2,9,1,0,0,0),(23,2,5,1,0,0,0),(24,2,8,1,0,0,0),(31,3,1,1,0,0,0),(32,3,2,1,0,1,0),(33,3,4,1,0,0,0),(34,4,1,1,0,0,0),(35,4,2,1,1,1,0),(36,4,4,1,0,1,0),(37,5,1,1,0,0,0),(38,5,6,1,1,1,0),(39,6,1,1,0,0,0),(40,6,3,1,1,1,0),(41,6,5,1,0,0,0),(42,6,6,1,1,0,0),(43,7,1,1,0,0,0),(44,7,2,1,1,0,0),(45,8,1,1,0,0,0),(46,8,2,1,0,1,0),(47,1,11,1,1,1,1),(48,1,10,1,1,1,1),(49,1,13,1,1,1,1),(50,1,12,1,1,1,1),(54,2,11,1,0,0,0),(55,2,10,1,0,0,0),(56,2,13,1,0,0,0),(57,2,12,1,0,0,0),(61,3,13,1,1,1,0),(62,5,10,1,1,1,0),(63,5,11,1,1,1,0),(64,5,12,1,1,0,0),(65,6,12,1,1,0,0);
/*!40000 ALTER TABLE `role_menu_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT 'notifikasi utk 1 user spesifik',
  `role_id` int DEFAULT NULL COMMENT 'notifikasi utk semua user di role ini',
  `pengajuan_id` int DEFAULT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,NULL,4,33,'Pengajuan Baru Perlu Disurvey','Pengajuan \"testing\" dari testing di testing menunggu survey lapangan.','detail_pengajuan.php?id=33',1,'2026-08-03 07:01:02'),(2,NULL,3,33,'Hasil Survey Menunggu Validasi','Pengajuan \"testing\" hasil survey menyatakan perlu pemangkasan, menunggu validasi Anda.','detail_pengajuan.php?id=33',1,'2026-08-03 07:01:30');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pohon_pending`
--

DROP TABLE IF EXISTS `pohon_pending`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pohon_pending` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_pohon` int DEFAULT NULL,
  `nama_lokal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_latin` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun_tanam` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `habitus` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_kel` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `volume` decimal(15,8) DEFAULT '0.00000000',
  `kelas_awet` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas_kuat` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `berat_jenis` decimal(15,8) DEFAULT '0.00000000',
  `kesehatan` enum('Sehat','Kurang Sehat','Sakit') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Sehat',
  `serapan_co` decimal(15,8) DEFAULT '0.00000000',
  `produksi_o` decimal(15,8) DEFAULT NULL,
  `nama_jalan` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelurahan` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kecamatan` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `koordinat_x` decimal(11,8) DEFAULT '0.00000000',
  `koordinat_y` decimal(10,8) DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `umur_pohon` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','valid','invalid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT 'pending = menunggu validasi, valid = sudah dipindah ke tabel pohon, invalid = ditolak',
  `catatan_validasi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'alasan otomatis/manual saat status invalid',
  `pohon_id` int DEFAULT NULL COMMENT 'diisi id di tabel pohon setelah baris ini divalidasi & dipindahkan',
  `dibuat_oleh` int DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `divalidasi_oleh` int DEFAULT NULL,
  `divalidasi_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pohon_pending_status` (`status`),
  KEY `fk_pohon_pending_pohon` (`pohon_id`),
  KEY `fk_pohon_pending_dibuat_oleh` (`dibuat_oleh`),
  KEY `fk_pohon_pending_divalidasi_oleh` (`divalidasi_oleh`),
  CONSTRAINT `fk_pohon_pending_dibuat_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `t_users` (`UserId`) ON DELETE SET NULL,
  CONSTRAINT `fk_pohon_pending_divalidasi_oleh` FOREIGN KEY (`divalidasi_oleh`) REFERENCES `t_users` (`UserId`) ON DELETE SET NULL,
  CONSTRAINT `fk_pohon_pending_pohon` FOREIGN KEY (`pohon_id`) REFERENCES `pohon` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Staging pohon baru sebelum divalidasi & dipindah ke tabel pohon';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pohon_pending`
--

LOCK TABLES `pohon_pending` WRITE;
/*!40000 ALTER TABLE `pohon_pending` DISABLE KEYS */;
INSERT INTO `pohon_pending` VALUES (1,NULL,'test','test','Meliaceae','2019','Pohon','Least Concern/Resiko Rendah',0.23101200,'I','II',299.99999900,'Sakit',1124.12345100,1560.87600000,'Jl. Cibabat','Cibabat','Cimahi Utara',107.53521400,-6.88954100,'','19',NULL,'valid',NULL,NULL,5,'2026-08-14 09:43:25',5,'2026-08-14 09:43:50');
/*!40000 ALTER TABLE `pohon_pending` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'dipakai sebagai $activePage di Admin/*',
  `label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'kelas ikon Bootstrap Icons',
  `url` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'nama file php relatif thd folder Admin/',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menus_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES (1,'dashboard','Dashboard','bi-speedometer2','index.php',10),(2,'pengajuan','Pengajuan Pemangkasan Pohon','bi-file-earmark-text','pengajuan.php',20),(3,'permohonan_bibit','Permohonan Bibit Tanaman','bi-flower1','permohonan_bibit.php',30),(4,'pohon','Kondisi Pohon','bi-tree','pohon.php',40),(5,'stok_bibit','Stok Bibit','bi-box-seam','stok_bibit.php',50),(6,'monitoring','Pemeliharaan / Monitoring RTH','bi-display','monitoring.php',60),(7,'map','Peta','bi-map','map.php',70),(8,'users','Manajemen Pengguna','bi-people','users.php',80),(9,'roles','Manajemen Role & Akses','bi-shield-lock','roles.php',90),(10,'pemakaian_pupuk','Pemakaian Pupuk','bi-flower2','pemakaian_pupuk.php',61),(11,'pemakaian_bbm','Pemakaian BBM','bi-fuel-pump','pemakaian_bbm.php',62),(12,'permintaan_sarpras','Permintaan Sarpras','bi-tools','permintaan_sarpras.php',63),(13,'pergantian_pohon','Pergantian Pohon','bi-receipt','pergantian_pohon.php',45);
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = peran bawaan dari dokumen kebutuhan, tidak bisa dihapus',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'superadmin','Superadmin','Akses penuh ke seluruh menu sistem',1,'2026-07-28 09:57:14'),(2,'admin','Admin','Melihat seluruh data internal & publik; tidak dapat menambah, mengubah, atau menghapus data apapun',1,'2026-07-28 09:57:14'),(3,'validator','Validator','Validasi hasil survey lapangan & rekap permohonan pemangkasan pohon',1,'2026-07-28 09:57:14'),(4,'petugas_survey','Petugas Survey Pohon','Survey lapangan atas permohonan pemangkasan & penandaan kondisi pohon',1,'2026-07-28 09:57:14'),(5,'petugas_pemeliharaan','Petugas Pemeliharaan RTH','Input pemakaian pupuk, pemakaian BBM, dan laporan pemeliharaan RTH',1,'2026-07-28 09:57:14'),(6,'petugas_penanaman','Petugas Penanaman','Input laporan penanaman pohon & permintaan bibit tanaman',1,'2026-07-28 09:57:14'),(7,'pelapor_pemangkasan','Pelapor Pemangkasan Pohon','Input pelaporan permohonan pemangkasan pohon dari kelurahan / LSM',1,'2026-07-28 09:57:14'),(8,'tim_tangkas','Tim Tangkas','Dokumentasi foto sebelum & sesudah eksekusi pemangkasan / penebangan pohon di lapangan',1,'2026-07-28 09:57:14');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliniasi_tajuk`
--

DROP TABLE IF EXISTS `deliniasi_tajuk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliniasi_tajuk` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pohon_id` int DEFAULT NULL COMMENT 'opsional, terhubung ke tabel pohon jika mewakili 1 pohon',
  `nama_lokasi` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `geometry` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT 'GeoJSON Polygon tajuk',
  `luas_m2` decimal(16,2) NOT NULL DEFAULT '0.00',
  `keterangan` text COLLATE utf8mb4_general_ci,
  `dibuat_oleh` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliniasi_tajuk`
--

LOCK TABLES `deliniasi_tajuk` WRITE;
/*!40000 ALTER TABLE `deliniasi_tajuk` DISABLE KEYS */;
/*!40000 ALTER TABLE `deliniasi_tajuk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliniasi_rth`
--

DROP TABLE IF EXISTS `deliniasi_rth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliniasi_rth` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_rth` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Taman/Hutan Kota/Jalur Hijau/Pemakaman/dll',
  `kecamatan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `geometry` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT 'GeoJSON Polygon [[lng,lat],...]',
  `luas_m2` decimal(16,2) NOT NULL DEFAULT '0.00' COMMENT 'dihitung otomatis dari geometry',
  `keterangan` text COLLATE utf8mb4_general_ci,
  `dibuat_oleh` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliniasi_rth`
--

LOCK TABLES `deliniasi_rth` WRITE;
/*!40000 ALTER TABLE `deliniasi_rth` DISABLE KEYS */;
/*!40000 ALTER TABLE `deliniasi_rth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pemakaian_pupuk`
--

DROP TABLE IF EXISTS `pemakaian_pupuk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pemakaian_pupuk` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `jenis_pupuk` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `satuan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'kg',
  `lokasi` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `petugas_id` int DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pemakaian_pupuk`
--

LOCK TABLES `pemakaian_pupuk` WRITE;
/*!40000 ALTER TABLE `pemakaian_pupuk` DISABLE KEYS */;
INSERT INTO `pemakaian_pupuk` VALUES (4,'2026-08-14','testing',1.00,'kg','Jl. Amir Machmud No. 120',5,'test',NULL,'2026-08-14 07:51:27'),(6,'2026-08-18','testing',0.02,'kg','Jl. Amir Machmud No. 120',5,'test',NULL,'2026-08-18 02:44:28');
/*!40000 ALTER TABLE `pemakaian_pupuk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tarif_pergantian`
--

DROP TABLE IF EXISTS `tarif_pergantian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarif_pergantian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis_pohon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `diameter_min` decimal(6,2) NOT NULL DEFAULT '0.00',
  `diameter_max` decimal(6,2) NOT NULL DEFAULT '999.00',
  `harga_per_cm` decimal(12,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarif_pergantian`
--

LOCK TABLES `tarif_pergantian` WRITE;
/*!40000 ALTER TABLE `tarif_pergantian` DISABLE KEYS */;
INSERT INTO `tarif_pergantian` VALUES (1,'Umum / Tidak Diketahui',0.00,999.00,50000.00,'Tarif default sebelum kajian standar harga resmi diinput admin'),(2,'Umum / Tidak Diketahui',0.00,999.00,50000.00,'Tarif default sebelum kajian standar harga resmi diinput admin'),(3,'Umum / Tidak Diketahui',0.00,999.00,50000.00,'Tarif default sebelum kajian standar harga resmi diinput admin');
/*!40000 ALTER TABLE `tarif_pergantian` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-19 13:14:45
