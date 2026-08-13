-- ============================================================
--  MIGRASI LANJUTAN Si-TANGKAL
--  Mencakup 4 modul sisa dari dokumen kebutuhan:
--   1) Alur pemangkasan berjenjang + notifikasi
--   2) Pemakaian pupuk / BBM / permintaan sarpras
--   3) Perhitungan pergantian pohon + surat resmi
--   4) Histori kondisi pohon (audit trail)
--
--  Jalankan SETELAH db/migration_rbac.sql:
--    mysql -u root db_sitangkal < db/migration_advanced.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- MODUL 1: ALUR PEMANGKASAN BERJENJANG + NOTIFIKASI
-- ============================================================

-- Tambah kolom tahapan ke tabel pengajuan yang sudah ada (tanpa
-- menghapus kolom lama: Survey_Pohon, Dokumentasi, DokumentasiAfter,
-- Keterangan tetap dipakai supaya kode lama tidak rusak).
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengajuan' AND COLUMN_NAME='status_tahap');
SET @sql := IF(@c=0, "ALTER TABLE pengajuan ADD COLUMN status_tahap ENUM('diajukan','disurvey','divalidasi','ditangani','selesai') NOT NULL DEFAULT 'diajukan' AFTER Keterangan", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengajuan' AND COLUMN_NAME='petugas_survey_id');
SET @sql := IF(@c=0, 'ALTER TABLE pengajuan ADD COLUMN petugas_survey_id INT NULL, ADD COLUMN hasil_survey ENUM(\'perlu_pemangkasan\',\'tidak_perlu\') NULL, ADD COLUMN survey_tanggal DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengajuan' AND COLUMN_NAME='validator_id');
SET @sql := IF(@c=0, 'ALTER TABLE pengajuan ADD COLUMN validator_id INT NULL, ADD COLUMN validasi_catatan TEXT NULL, ADD COLUMN validasi_tanggal DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengajuan' AND COLUMN_NAME='tim_tangkas_id');
SET @sql := IF(@c=0, 'ALTER TABLE pengajuan ADD COLUMN tim_tangkas_id INT NULL, ADD COLUMN eksekusi_tanggal DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Notifikasi berjenjang. Bisa ditujukan ke 1 user (user_id) atau ke
-- semua pemegang 1 role (role_id) — misal "semua Petugas Survey".
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `user_id`      INT NULL COMMENT 'notifikasi utk 1 user spesifik',
  `role_id`      INT NULL COMMENT 'notifikasi utk semua user di role ini',
  `pengajuan_id` INT NULL,
  `title`        VARCHAR(150) NOT NULL,
  `message`      VARCHAR(255) NOT NULL,
  `link`         VARCHAR(150) DEFAULT NULL,
  `is_read`      TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- MODUL 2: PEMAKAIAN PUPUK / BBM / PERMINTAAN SARPRAS
-- ============================================================

CREATE TABLE IF NOT EXISTS `pemakaian_pupuk` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `tanggal`          DATE NOT NULL,
  `jenis_pupuk`      VARCHAR(100) NOT NULL,
  `jumlah`           DECIMAL(10,2) NOT NULL,
  `satuan`           VARCHAR(20) NOT NULL DEFAULT 'kg',
  `lokasi`           VARCHAR(150) NOT NULL,
  `petugas_id`       INT NULL,
  `keterangan`       TEXT NULL,
  `foto`             VARCHAR(255) DEFAULT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pemakaian_bbm` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `tanggal`          DATE NOT NULL,
  `jenis_bbm`        VARCHAR(50) NOT NULL,
  `jumlah_liter`     DECIMAL(10,2) NOT NULL,
  `nominal_rupiah`   DECIMAL(14,2) NOT NULL DEFAULT 0,
  `kendaraan`        VARCHAR(100) DEFAULT NULL,
  `petugas_id`       INT NULL,
  `keterangan`       TEXT NULL,
  `bukti`            VARCHAR(255) DEFAULT NULL COMMENT 'foto struk/kupon',
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `permintaan_sarpras` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `tanggal`          DATE NOT NULL,
  `nama_barang`      VARCHAR(150) NOT NULL,
  `jumlah`           INT NOT NULL DEFAULT 1,
  `satuan`           VARCHAR(20) NOT NULL DEFAULT 'unit',
  `alasan`           TEXT NULL,
  `status`           ENUM('diajukan','disetujui','ditolak','selesai') NOT NULL DEFAULT 'diajukan',
  `catatan_admin`    TEXT NULL,
  `pemohon_id`       INT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- MODUL 3: PERHITUNGAN PERGANTIAN POHON + SURAT
-- ============================================================

-- Tarif dasar per jenis & rentang diameter — dikelola manual lewat
-- UI (Superadmin/Admin) karena dokumen menyebut "kajian standar
-- harga" tanpa melampirkan rumus pastinya. Formula yang dipakai:
--   total_biaya = jumlah_pohon * diameter_cm * harga_per_cm
-- Jika kajian standar harga resmi sudah ada, cukup ubah data di
-- tabel ini (atau field harga_per_cm-nya), tidak perlu ubah kode.
CREATE TABLE IF NOT EXISTS `tarif_pergantian` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `jenis_pohon`    VARCHAR(100) NOT NULL,
  `diameter_min`   DECIMAL(6,2) NOT NULL DEFAULT 0,
  `diameter_max`   DECIMAL(6,2) NOT NULL DEFAULT 999,
  `harga_per_cm`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `keterangan`     VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tarif_pergantian` (`jenis_pohon`, `diameter_min`, `diameter_max`, `harga_per_cm`, `keterangan`) VALUES
('Umum / Tidak Diketahui', 0, 999, 50000, 'Tarif default sebelum kajian standar harga resmi diinput admin')
ON DUPLICATE KEY UPDATE jenis_pohon = jenis_pohon;

CREATE TABLE IF NOT EXISTS `pergantian_pohon` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `pengajuan_id`    INT NULL COMMENT 'terhubung ke pengajuan pemangkasan/penebangan jika ada',
  `pohon_id`        INT NULL,
  `jenis_pohon`     VARCHAR(100) NOT NULL,
  `diameter_cm`     DECIMAL(6,2) NOT NULL,
  `jumlah_pohon`    INT NOT NULL DEFAULT 1,
  `tarif_id`        INT NULL,
  `harga_per_cm`    DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_biaya`     DECIMAL(14,2) NOT NULL DEFAULT 0,
  `nomor_surat`     VARCHAR(100) DEFAULT NULL,
  `tanggal_surat`   DATE DEFAULT NULL,
  `nama_kabid`      VARCHAR(100) DEFAULT NULL,
  `nip_kabid`       VARCHAR(40) DEFAULT NULL,
  `dibuat_oleh`     INT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- MODUL 4: HISTORI KONDISI POHON (AUDIT TRAIL)
-- ============================================================

CREATE TABLE IF NOT EXISTS `pohon_histori` (
  `id`                INT NOT NULL AUTO_INCREMENT,
  `pohon_id`          INT NOT NULL,
  `kondisi_kesehatan` VARCHAR(50) DEFAULT NULL,
  `umur_pohon`        VARCHAR(50) DEFAULT NULL,
  `keterangan`        TEXT NULL,
  `snapshot_json`     JSON NULL COMMENT 'salinan lengkap baris pohon sebelum diubah',
  `diubah_oleh`       INT NULL,
  `diubah_pada`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_histori_pohon` (`pohon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- DAFTARKAN MENU BARU KE RBAC (menyambung db/migration_rbac.sql)
-- ============================================================
INSERT INTO `menus` (`id`, `code`, `label`, `icon`, `url`, `sort_order`) VALUES
(10, 'pemakaian_pupuk',    'Pemakaian Pupuk',        'bi-flower2',        'pemakaian_pupuk.php',    61),
(11, 'pemakaian_bbm',      'Pemakaian BBM',          'bi-fuel-pump',      'pemakaian_bbm.php',      62),
(12, 'permintaan_sarpras', 'Permintaan Sarpras',     'bi-tools',          'permintaan_sarpras.php', 63),
(13, 'pergantian_pohon',   'Pergantian Pohon',       'bi-receipt',        'pergantian_pohon.php',   45)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `icon` = VALUES(`icon`), `url` = VALUES(`url`);

-- Superadmin: otomatis full access ke menu baru (mengulang pola migration_rbac.sql)
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 1, m.id, 1, 1, 1, 1 FROM `menus` m WHERE m.id IN (10,11,12,13)
ON DUPLICATE KEY UPDATE can_view=1, can_create=1, can_edit=1, can_delete=1;

-- Admin: view-only ke menu baru (konsisten dgn kebijakan "lihat semua, tidak bisa ubah")
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 2, m.id, 1, 0, 0, 0 FROM `menus` m WHERE m.id IN (10,11,12,13)
ON DUPLICATE KEY UPDATE can_view=1, can_create=0, can_edit=0, can_delete=0;

-- Validator: lihat pergantian pohon (rekap biaya) & bisa mengisi data surat/perhitungan
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(3, 13, 1, 1, 1, 0)
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

-- Petugas Pemeliharaan RTH: input pupuk & BBM
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(5, 10, 1, 1, 1, 0),
(5, 11, 1, 1, 1, 0),
(5, 12, 1, 1, 0, 0)
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

-- Petugas Penanaman: bisa mengajukan permintaan sarpras juga
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(6, 12, 1, 1, 0, 0)
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

SET FOREIGN_KEY_CHECKS = 1;
