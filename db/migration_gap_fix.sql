-- ============================================================
--  MIGRASI: Pelengkap modul yang belum/kurang sesuai dokumen
--  kebutuhan "Akun SiTANGKAL" (skema_aplikasi_23_juli_2026)
--
--  Menambahkan:
--   1. Laporan Penanaman (terpisah dari Permohonan Bibit)
--   2. Pemakaian BBM berbasis kupon (terima dari, penerima,
--      keperluan, jumlah kupon, nominal kupon)
--   3. Permintaan Sarpras: tambah terima dari & penerima
--   4. Peta: Deliniasi RTH (+ % RTH otomatis), Deliniasi Tajuk
--      Pohon, Penanaman & Potensi Penanaman
--   5. Penyajian Data: Nilai IKTL & Persentase RTH per tahun
--
--  Jalankan SEKALI di database db_sitangkal, SETELAH
--  migration_rbac.sql:
--    mysql -u root db_sitangkal < db/migration_gap_fix.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. LAPORAN PENANAMAN
--    (Petugas Penanaman — Pak Ahmad — terpisah dari permohonan
--    bibit: tanggal, lokasi, koordinat lokasi, asal bibit,
--    jumlah bibit, keterangan)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `laporan_penanaman` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `tanggal`        DATE NOT NULL,
  `lokasi`         VARCHAR(150) NOT NULL,
  `latitude`       DECIMAL(10,7) DEFAULT NULL,
  `longitude`      DECIMAL(10,7) DEFAULT NULL,
  `asal_bibit`     VARCHAR(150) NOT NULL COMMENT 'mis. Stok Bibit Dinas / Bantuan / Swadaya / CSR',
  `jenis_tanaman`  VARCHAR(100) DEFAULT NULL,
  `jumlah_bibit`   INT NOT NULL DEFAULT 0,
  `keterangan`     TEXT NULL,
  `foto`           VARCHAR(255) DEFAULT NULL,
  `petugas_id`     INT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 2. PEMAKAIAN BBM — tambah skema kupon sesuai dokumen
--    (terima dari, penerima, keperluan, jumlah kupon, nominal
--    kupon). Kolom lama (jenis_bbm, jumlah_liter, kendaraan)
--    TETAP ADA untuk kompatibilitas data lama, dijadikan opsional.
-- ------------------------------------------------------------
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pemakaian_bbm' AND COLUMN_NAME='terima_dari');
SET @sql := IF(@col=0, 'ALTER TABLE pemakaian_bbm ADD COLUMN terima_dari VARCHAR(100) NULL AFTER tanggal', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pemakaian_bbm' AND COLUMN_NAME='penerima');
SET @sql := IF(@col=0, 'ALTER TABLE pemakaian_bbm ADD COLUMN penerima VARCHAR(100) NULL AFTER terima_dari', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pemakaian_bbm' AND COLUMN_NAME='keperluan');
SET @sql := IF(@col=0, 'ALTER TABLE pemakaian_bbm ADD COLUMN keperluan VARCHAR(150) NULL AFTER penerima', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pemakaian_bbm' AND COLUMN_NAME='jumlah_kupon');
SET @sql := IF(@col=0, 'ALTER TABLE pemakaian_bbm ADD COLUMN jumlah_kupon INT NULL AFTER keperluan', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pemakaian_bbm' AND COLUMN_NAME='nominal_kupon');
SET @sql := IF(@col=0, 'ALTER TABLE pemakaian_bbm ADD COLUMN nominal_kupon DECIMAL(14,2) NULL AFTER jumlah_kupon', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- kolom lama dibuat boleh kosong (dulu NOT NULL)
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pemakaian_bbm' AND COLUMN_NAME='jenis_bbm' AND IS_NULLABLE='NO');
SET @sql := IF(@col>0, 'ALTER TABLE pemakaian_bbm MODIFY jenis_bbm VARCHAR(50) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pemakaian_bbm' AND COLUMN_NAME='jumlah_liter' AND IS_NULLABLE='NO');
SET @sql := IF(@col>0, 'ALTER TABLE pemakaian_bbm MODIFY jumlah_liter DECIMAL(10,2) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 3. PERMINTAAN SARPRAS — tambah terima dari & penerima
-- ------------------------------------------------------------
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='permintaan_sarpras' AND COLUMN_NAME='terima_dari');
SET @sql := IF(@col=0, 'ALTER TABLE permintaan_sarpras ADD COLUMN terima_dari VARCHAR(100) NULL AFTER tanggal', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='permintaan_sarpras' AND COLUMN_NAME='penerima');
SET @sql := IF(@col=0, 'ALTER TABLE permintaan_sarpras ADD COLUMN penerima VARCHAR(100) NULL AFTER terima_dari', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 4. PETA — DELINIASI RTH, DELINIASI TAJUK, PENANAMAN & POTENSI
-- ------------------------------------------------------------

-- 4a. Deliniasi RTH: setiap deliniasi = 1 poligon area RTH.
--     luas_m2 dihitung otomatis dari geometry (lihat DeliniasiModel).
--     Persentase RTH = total luas_m2 seluruh baris / luas wilayah kota.
CREATE TABLE IF NOT EXISTS `deliniasi_rth` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `nama_lokasi`   VARCHAR(150) NOT NULL,
  `jenis_rth`     VARCHAR(100) DEFAULT NULL COMMENT 'Taman/Hutan Kota/Jalur Hijau/Pemakaman/dll',
  `kecamatan`     VARCHAR(100) DEFAULT NULL,
  `geometry`      LONGTEXT NOT NULL COMMENT 'GeoJSON Polygon [[lng,lat],...]',
  `luas_m2`       DECIMAL(16,2) NOT NULL DEFAULT 0 COMMENT 'dihitung otomatis dari geometry',
  `keterangan`    TEXT NULL,
  `dibuat_oleh`   INT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4b. Deliniasi Tajuk Pohon: poligon tutupan tajuk per pohon/klaster pohon.
CREATE TABLE IF NOT EXISTS `deliniasi_tajuk` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `pohon_id`      INT NULL COMMENT 'opsional, terhubung ke tabel pohon jika mewakili 1 pohon',
  `nama_lokasi`   VARCHAR(150) NOT NULL,
  `geometry`      LONGTEXT NOT NULL COMMENT 'GeoJSON Polygon tajuk',
  `luas_m2`       DECIMAL(16,2) NOT NULL DEFAULT 0,
  `keterangan`    TEXT NULL,
  `dibuat_oleh`   INT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4c. Penanaman & Potensi Penanaman: titik/area realisasi tanam ATAU
--     potensi tanam (dari kajian potensi penanaman Kota Cimahi 2026,
--     anggaran perubahan).
CREATE TABLE IF NOT EXISTS `potensi_penanaman` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `tipe`          ENUM('realisasi','potensi') NOT NULL DEFAULT 'potensi',
  `nama_lokasi`   VARCHAR(150) NOT NULL,
  `kecamatan`     VARCHAR(100) DEFAULT NULL,
  `geometry`      LONGTEXT NOT NULL COMMENT 'GeoJSON Point atau Polygon',
  `luas_m2`       DECIMAL(16,2) DEFAULT NULL COMMENT 'diisi jika geometry berupa polygon',
  `estimasi_jumlah_pohon` INT DEFAULT NULL,
  `sumber_kajian` VARCHAR(150) DEFAULT 'Kajian Potensi Penanaman Kota Cimahi 2026 (Anggaran Perubahan)',
  `keterangan`    TEXT NULL,
  `dibuat_oleh`   INT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 5. PENYAJIAN DATA — Nilai IKTL & Persentase RTH per tahun
--    (diinput manual, disajikan tabel & diagram)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `penyajian_data` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `jenis`       ENUM('iktl','rth_persen') NOT NULL,
  `tahun`       YEAR NOT NULL,
  `nilai`       DECIMAL(10,4) NOT NULL COMMENT 'Nilai IKTL, atau persentase RTH (0-100)',
  `keterangan`  TEXT NULL,
  `dibuat_oleh` INT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jenis_tahun` (`jenis`, `tahun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 6. MENU BARU + IZIN AKSES (role_menu_access)
-- ------------------------------------------------------------
INSERT INTO `menus` (`id`, `code`, `label`, `icon`, `url`, `sort_order`) VALUES
(10, 'laporan_penanaman', 'Laporan Penanaman',           'bi-tree-fill',      'laporan_penanaman.php', 35),
(11, 'peta_deliniasi',    'Peta Deliniasi & Potensi',    'bi-bounding-box',   'peta_deliniasi.php',    72),
(12, 'penyajian_data',    'Penyajian Data (IKTL & RTH)', 'bi-bar-chart-line', 'penyajian_data.php',    95)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `icon` = VALUES(`icon`), `url` = VALUES(`url`);

-- Superadmin: full access ke 3 menu baru
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 1, m.id, 1, 1, 1, 1 FROM `menus` m WHERE m.code IN ('laporan_penanaman','peta_deliniasi','penyajian_data')
ON DUPLICATE KEY UPDATE can_view=1, can_create=1, can_edit=1, can_delete=1;

-- Admin (Seksi Konservasi): view-only semua menu baru
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 2, m.id, 1, 0, 0, 0 FROM `menus` m WHERE m.code IN ('laporan_penanaman','peta_deliniasi','penyajian_data')
ON DUPLICATE KEY UPDATE can_view=1, can_create=0, can_edit=0, can_delete=0;

-- Validator (Kabid): lihat rekap Laporan Penanaman & Penyajian Data
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 3, m.id, 1, 0, 0, 0 FROM `menus` m WHERE m.code IN ('laporan_penanaman','peta_deliniasi','penyajian_data')
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view);

-- Petugas Penanaman (Pak Ahmad): input Laporan Penanaman penuh
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 6, m.id, 1, 1, 1, 0 FROM `menus` m WHERE m.code = 'laporan_penanaman'
ON DUPLICATE KEY UPDATE can_view=1, can_create=1, can_edit=1, can_delete=0;

-- Petugas Penanaman juga bisa lihat & input peta potensi/realisasi tanam
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 6, m.id, 1, 1, 0, 0 FROM `menus` m WHERE m.code = 'peta_deliniasi'
ON DUPLICATE KEY UPDATE can_view=1, can_create=1;

-- Petugas Pemeliharaan RTH (Pak Nandang): input deliniasi RTH & tajuk
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 5, m.id, 1, 1, 1, 0 FROM `menus` m WHERE m.code = 'peta_deliniasi'
ON DUPLICATE KEY UPDATE can_view=1, can_create=1, can_edit=1;

-- Petugas Survey Pohon: boleh input deliniasi tajuk pohon (terkait penandaan pohon)
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 4, m.id, 1, 1, 0, 0 FROM `menus` m WHERE m.code = 'peta_deliniasi'
ON DUPLICATE KEY UPDATE can_view=1, can_create=1;

SET FOREIGN_KEY_CHECKS = 1;
