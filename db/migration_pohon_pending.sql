-- ============================================================
--  Migrasi: Tabel staging `pohon_pending`
-- ------------------------------------------------------------
--  Tujuan: setiap kali user menambah data pohon baru, data TIDAK
--  langsung masuk ke tabel `pohon`. Data disimpan dulu di sini
--  (status = 'pending'), lalu divalidasi (otomatis oleh sistem
--  dan/atau manual oleh admin). Jika valid -> baris dipindahkan
--  ke tabel `pohon` (lewat PohonModel::create) dan baris ini
--  ditandai status='valid' + pohon_id diisi. Jika tidak valid ->
--  status='invalid' + catatan_validasi diisi alasan penolakan.
--
--  Jalankan file ini SEKALI di database db_sitangkal yang sudah
--  ada tabel `pohon` dan `t_users`.
-- ============================================================

CREATE TABLE IF NOT EXISTS `pohon_pending` (
  `id` INT NOT NULL AUTO_INCREMENT,

  -- ==== Kolom ini SAMA PERSIS dengan tabel `pohon` ====
  `no_pohon` INT DEFAULT NULL,
  `nama_lokal` VARCHAR(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_latin` VARCHAR(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family` VARCHAR(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun_tanam` VARCHAR(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `habitus` VARCHAR(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_kel` VARCHAR(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `volume` DECIMAL(15,8) DEFAULT '0.00000000',
  `kelas_awet` VARCHAR(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas_kuat` VARCHAR(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `berat_jenis` DECIMAL(15,8) DEFAULT '0.00000000',
  `kesehatan` ENUM('Sehat','Kurang Sehat','Sakit') COLLATE utf8mb4_general_ci DEFAULT 'Sehat',
  `serapan_co` DECIMAL(15,8) DEFAULT '0.00000000',
  `produksi_o` DECIMAL(15,8) DEFAULT NULL,
  `nama_jalan` VARCHAR(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelurahan` VARCHAR(70) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kecamatan` VARCHAR(70) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `koordinat_x` DECIMAL(11,8) DEFAULT '0.00000000',
  `koordinat_y` DECIMAL(10,8) DEFAULT NULL,
  `keterangan` TEXT COLLATE utf8mb4_general_ci,
  `umur_pohon` VARCHAR(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto` VARCHAR(150) COLLATE utf8mb4_general_ci DEFAULT NULL,

  -- ==== Kolom khusus staging / alur validasi ====
  `status` ENUM('pending','valid','invalid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending'
      COMMENT 'pending = menunggu validasi, valid = sudah dipindah ke tabel pohon, invalid = ditolak',
  `catatan_validasi` TEXT COLLATE utf8mb4_general_ci DEFAULT NULL
      COMMENT 'alasan otomatis/manual saat status invalid',
  `pohon_id` INT DEFAULT NULL
      COMMENT 'diisi id di tabel pohon setelah baris ini divalidasi & dipindahkan',
  `dibuat_oleh` INT DEFAULT NULL,
  `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `divalidasi_oleh` INT DEFAULT NULL,
  `divalidasi_pada` DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_pohon_pending_status` (`status`),
  KEY `fk_pohon_pending_pohon` (`pohon_id`),
  KEY `fk_pohon_pending_dibuat_oleh` (`dibuat_oleh`),
  KEY `fk_pohon_pending_divalidasi_oleh` (`divalidasi_oleh`),
  CONSTRAINT `fk_pohon_pending_pohon` FOREIGN KEY (`pohon_id`) REFERENCES `pohon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pohon_pending_dibuat_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `t_users` (`UserId`) ON DELETE SET NULL,
  CONSTRAINT `fk_pohon_pending_divalidasi_oleh` FOREIGN KEY (`divalidasi_oleh`) REFERENCES `t_users` (`UserId`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Staging pohon baru sebelum divalidasi & dipindah ke tabel pohon';
