-- =====================================================================
-- Migration: Menu Dokumen (Download Dokumen)
-- Tanggal   : 2026-08-26
-- Catatan   : Sesuai skema terbaru 20 Agustus 2026, poin 6a — menu baru
--             "Dokumen" berisi daftar nama dokumen + tombol unduh.
--             Aman dijalankan ulang (idempotent) selama tabel/kolom
--             belum ada.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) TABEL DOKUMEN
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dokumen` (
    `id`            INT NOT NULL AUTO_INCREMENT,
    `nama_dokumen`  VARCHAR(255) NOT NULL,
    `keterangan`    VARCHAR(255) DEFAULT NULL,
    `file_path`     VARCHAR(255) NOT NULL COMMENT 'path relatif thd folder root aplikasi',
    `file_asli`     VARCHAR(255) DEFAULT NULL COMMENT 'nama file asli saat diupload',
    `ukuran_bytes`  INT DEFAULT NULL,
    `diunggah_oleh` INT DEFAULT NULL,
    `dibuat_pada`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_dokumen_user` (`diunggah_oleh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 2) DAFTARKAN MENU "Dokumen" (tampil sebagai grup sendiri di sidebar,
--    sejajar dengan Peta / Penyajian Data — lihat Admin/layouts/sidebar.php)
-- ---------------------------------------------------------------------
INSERT INTO `menus` (`code`, `label`, `icon`, `url`, `sort_order`)
SELECT 'dokumen', 'Dokumen', 'bi-file-earmark-arrow-down', 'dokumen.php', 75
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `code` = 'dokumen');

-- ---------------------------------------------------------------------
-- 3) AKSES RBAC
--    - Superadmin (role_id=1) otomatis full-access (bypass di Rbac.php).
--    - Semua role lain diberi akses VIEW + unduh (can_view=1) supaya
--      dokumen bisa diunduh oleh siapa pun yang login.
--    - Upload/hapus dokumen (can_create/can_delete) hanya untuk
--      Admin (role_id=2), sesuaikan lagi lewat menu Manajemen Role
--      jika ternyata perlu peran lain.
-- ---------------------------------------------------------------------
INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`)
SELECT r.id, m.id, 1, 0, 0, 0
FROM `roles` r
CROSS JOIN `menus` m
WHERE m.code = 'dokumen'
  AND r.id <> 1
  AND NOT EXISTS (
      SELECT 1 FROM `role_menu_access` rma WHERE rma.role_id = r.id AND rma.menu_id = m.id
  );

UPDATE `role_menu_access` rma
INNER JOIN `menus` m ON m.id = rma.menu_id AND m.code = 'dokumen'
SET rma.can_create = 1, rma.can_delete = 1
WHERE rma.role_id = 2;
