-- ============================================================
--  MIGRASI: Modul RBAC (Role-Based Access Control) Si-TANGKAL
--  Menambahkan peran granular sesuai dokumen kebutuhan
--  "Akun SiTANGKAL" (skema_aplikasi_23_juli_2026) tanpa
--  menghapus data/kolom yang sudah ada (t_users.Type tetap ada,
--  dipakai sebagai label tampilan & kompatibilitas kode lama).
--
--  Jalankan SEKALI di database db_sitangkal:
--    mysql -u root db_sitangkal < db/migration_rbac.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. TABEL ROLES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(40)  NOT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = peran bawaan dari dokumen kebutuhan, tidak bisa dihapus',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 2. TABEL MENUS  (mewakili setiap menu/modul di sidebar Admin)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menus` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `code`       VARCHAR(40)  NOT NULL COMMENT 'dipakai sebagai $activePage di Admin/*',
  `label`      VARCHAR(100) NOT NULL,
  `icon`       VARCHAR(60)  DEFAULT NULL COMMENT 'kelas ikon Bootstrap Icons',
  `url`        VARCHAR(100) NOT NULL COMMENT 'nama file php relatif thd folder Admin/',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menus_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 3. TABEL ROLE_MENU_ACCESS (matriks izin per peran per menu)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_menu_access` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `role_id`    INT NOT NULL,
  `menu_id`    INT NOT NULL,
  `can_view`   TINYINT(1) NOT NULL DEFAULT 0,
  `can_create` TINYINT(1) NOT NULL DEFAULT 0,
  `can_edit`   TINYINT(1) NOT NULL DEFAULT 0,
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_menu` (`role_id`, `menu_id`),
  KEY `fk_rma_role` (`role_id`),
  KEY `fk_rma_menu` (`menu_id`),
  CONSTRAINT `fk_rma_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rma_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 4. TAMBAH KOLOM role_id DI t_users (Type lama tetap dipakai
--    sebagai label; role_id dipakai untuk cek izin RBAC)
-- ------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 't_users' AND COLUMN_NAME = 'role_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE t_users ADD COLUMN role_id INT NULL AFTER Type, ADD KEY fk_users_role (role_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 5. SEED ROLES — 7 akun dari dokumen + Tim Tangkas (disebut
--    terpisah di bagian "Akses input data" dokumen)
-- ------------------------------------------------------------
INSERT INTO `roles` (`id`, `code`, `name`, `description`, `is_system`) VALUES
(1, 'superadmin',           'Superadmin',                  'Akses penuh ke seluruh menu', 1),
(2, 'admin',                'Admin',                       'Seksi Konservasi — lihat semua data internal & publik, tidak bisa mengubah data apapun', 1),
(3, 'validator',            'Validator',                   'Kabid (Pak Eko) — validasi hasil survey & rekap permohonan pemangkasan', 1),
(4, 'petugas_survey',       'Petugas Survey Pohon',        'Pak Anto, Pak Abdul Hapid — survey permohonan pemangkasan & penandaan pohon', 1),
(5, 'petugas_pemeliharaan', 'Petugas Pemeliharaan RTH',    'Pak Nandang — input pemakaian pupuk & pemeliharaan RTH', 1),
(6, 'petugas_penanaman',    'Petugas Penanaman',           'Pak Ahmad — input laporan penanaman & permintaan bibit', 1),
(7, 'pelapor_pemangkasan',  'Pelapor Pemangkasan Pohon',   'Kelurahan / LSM tertentu — input pelaporan pemangkasan', 1),
(8, 'tim_tangkas',          'Tim Tangkas',                 'Input foto sebelum & sesudah penebangan/pemangkasan', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- ------------------------------------------------------------
-- 6. SEED MENUS — sesuai sidebar Admin yang sudah ada + menu baru
-- ------------------------------------------------------------
INSERT INTO `menus` (`id`, `code`, `label`, `icon`, `url`, `sort_order`) VALUES
(1, 'dashboard',         'Dashboard',                          'bi-speedometer2',       'index.php',              10),
(2, 'pengajuan',         'Pengajuan Pemangkasan Pohon',         'bi-file-earmark-text',  'pengajuan.php',          20),
(3, 'permohonan_bibit',  'Permohonan Bibit Tanaman',            'bi-flower1',            'permohonan_bibit.php',   30),
(4, 'pohon',             'Kondisi Pohon',                       'bi-tree',               'pohon.php',              40),
(5, 'stok_bibit',        'Stok Bibit',                          'bi-box-seam',           'stok_bibit.php',         50),
(6, 'monitoring',        'Pemeliharaan / Monitoring RTH',       'bi-display',            'monitoring.php',         60),
(7, 'map',               'Peta',                                'bi-map',                'map.php',                70),
(8, 'users',             'Manajemen Pengguna',                  'bi-people',             'users.php',              80),
(9, 'roles',             'Manajemen Role & Akses',              'bi-shield-lock',        'roles.php',              90)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `icon` = VALUES(`icon`), `url` = VALUES(`url`);

-- ------------------------------------------------------------
-- 7. SEED MATRIKS IZIN (role_menu_access) sesuai tabel
--    "Akses input data Akun SiTANGKAL" pada dokumen kebutuhan
-- ------------------------------------------------------------

-- 7a. Superadmin: akses penuh ke SEMUA menu
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 1, m.id, 1, 1, 1, 1 FROM `menus` m
ON DUPLICATE KEY UPDATE can_view=1, can_create=1, can_edit=1, can_delete=1;

-- 7b. Admin (Seksi Konservasi): lihat semua data internal & publik,
--     TIDAK BISA mengubah data apapun (view=1 di semua, create/edit/delete=0)
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT 2, m.id, 1, 0, 0, 0 FROM `menus` m
ON DUPLICATE KEY UPDATE can_view=1, can_create=0, can_edit=0, can_delete=0;

-- 7c. Validator (Kabid): dashboard, validasi & rekap pengajuan, lihat kondisi pohon
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(3, 1, 1, 0, 0, 0),  -- dashboard: view
(3, 2, 1, 0, 1, 0),  -- pengajuan: view + edit (validasi hasil survey)
(3, 4, 1, 0, 0, 0)   -- pohon: view
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

-- 7d. Petugas Survey Pohon: input hasil survey pemangkasan, tandai pohon perlu pemeliharaan
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(4, 1, 1, 0, 0, 0),  -- dashboard
(4, 2, 1, 1, 1, 0),  -- pengajuan: view + input hasil survey (create/edit), tidak hapus
(4, 4, 1, 0, 1, 0)   -- pohon: view + tandai kondisi (edit)
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

-- 7e. Petugas Pemeliharaan RTH: input pemakaian pupuk & pemeliharaan RTH (menu monitoring)
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(5, 1, 1, 0, 0, 0),
(5, 6, 1, 1, 1, 0)   -- monitoring: view + create + edit data sendiri
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

-- 7f. Petugas Penanaman: input laporan penanaman (sementara di menu monitoring,
--     lihat CATATAN di Admin/core/Rbac.php) + input permintaan bibit + lihat stok bibit
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(6, 1, 1, 0, 0, 0),
(6, 3, 1, 1, 1, 0),  -- permohonan_bibit: view + create + edit
(6, 5, 1, 0, 0, 0),  -- stok_bibit: view
(6, 6, 1, 1, 0, 0)   -- monitoring: view + create (laporan penanaman)
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

-- 7g. Pelapor Pemangkasan (kelurahan/LSM): hanya input pelaporan pemangkasan
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(7, 1, 1, 0, 0, 0),
(7, 2, 1, 1, 0, 0)   -- pengajuan: view (punya sendiri) + create (lapor)
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

-- 7h. Tim Tangkas: input foto sebelum/sesudah penebangan pada pengajuan
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(8, 1, 1, 0, 0, 0),
(8, 2, 1, 0, 1, 0)   -- pengajuan: view + edit (upload dokumentasi before/after)
ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);

-- ------------------------------------------------------------
-- 8. MIGRASI DATA USER LAMA -> role_id
--    (t_users.Type saat ini bebas teks: 'Administrator', 'ADMIN',
--    'Petugas Lapangan', dst. Dipetakan best-effort ke role baru;
--    SILAKAN DIPERIKSA & DISESUAIKAN MANUAL lewat menu "Manajemen
--    Pengguna" setelah migrasi, terutama untuk 'Petugas Lapangan'
--    yang paling mendekati peran 'Petugas Survey Pohon' di dokumen.)
-- ------------------------------------------------------------
UPDATE `t_users` SET role_id = 1 WHERE role_id IS NULL AND (Type = 'Administrator' OR Type = 'ADMIN' OR Type = 'Superadmin');
UPDATE `t_users` SET role_id = 4 WHERE role_id IS NULL AND Type = 'Petugas Lapangan';
UPDATE `t_users` SET role_id = 2 WHERE role_id IS NULL; -- fallback: Admin (paling aman, view-only)

SET FOREIGN_KEY_CHECKS = 1;
