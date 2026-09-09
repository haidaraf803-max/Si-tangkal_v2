-- =====================================================================
-- Migration: Perubahan alur Pengajuan Pemangkasan & Permohonan Bibit
-- Tanggal   : 2026-08-24
-- Catatan   : Jalankan sekali di database `db_sitangkal`. Aman dijalankan
--             ulang (idempotent) selama kolom belum ada — cek dulu bila
--             ragu (SHOW COLUMNS FROM ...).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) PENGAJUAN PEMANGKASAN POHON
--    Langkah "Divalidasi" oleh Validator dihapus dari alur. Data lama
--    yang masih tersangkut di status_tahap = 'divalidasi' / 'ditangani'
--    dikembalikan ke 'disurvey' supaya langsung muncul di antrean
--    Tim Tangkas untuk dieksekusi (upload foto sesudah).
-- ---------------------------------------------------------------------
UPDATE `pengajuan`
SET `status_tahap` = 'disurvey'
WHERE `status_tahap` IN ('divalidasi', 'ditangani');

-- ---------------------------------------------------------------------
-- 2) PERMOHONAN BIBIT
--    Tambah kolom untuk tahap "serah terima" bibit + status baru
--    'Selesai' (dicapai setelah foto & tanggal serah terima diisi).
-- ---------------------------------------------------------------------
ALTER TABLE `permohonan_bibit`
    ADD COLUMN `tanggal_disetujui`     DATE         NULL DEFAULT NULL AFTER `status_permohonan`,
    ADD COLUMN `foto_serah_terima`     VARCHAR(255) NULL DEFAULT NULL AFTER `tanggal_disetujui`,
    ADD COLUMN `tanggal_serah_terima`  DATE         NULL DEFAULT NULL AFTER `foto_serah_terima`;

ALTER TABLE `permohonan_bibit`
    MODIFY COLUMN `status_permohonan`
    ENUM('Belum','Disetujui','Ditolak','Selesai') COLLATE utf8mb4_general_ci DEFAULT 'Belum';

-- ---------------------------------------------------------------------
-- 3) NOTIFICATIONS
--    Tambah kolom bibit_id agar notifikasi permohonan bibit (mis. untuk
--    "tim pemeliharaan" / Petugas Penanaman, role_id = 6) bisa dilacak
--    dan ditandai selesai per-permohonan, terpisah dari pengajuan_id
--    yang sudah dipakai untuk alur pemangkasan.
-- ---------------------------------------------------------------------
ALTER TABLE `notifications`
    ADD COLUMN `bibit_id` INT NULL DEFAULT NULL AFTER `pengajuan_id`,
    ADD KEY `idx_notif_bibit` (`bibit_id`);
