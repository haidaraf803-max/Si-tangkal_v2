-- =====================================================================
-- Migration: Rename menu "Pemeliharaan / Monitoring RTH" -> "Monitoring Pohon"
-- Tanggal   : 2026-08-26
-- Catatan   : Sesuai skema terbaru 20 Agustus 2026, poin 5a — menu
--             "Pemeliharaan / Monitoring RTH" diganti nama jadi
--             "Monitoring Pohon" dan dipindahkan (secara tampilan) ke
--             dalam grup "Inventarisasi Pohon". Pemindahan grup di
--             sidebar sudah ditangani di Admin/layouts/sidebar.php
--             (perubahan tampilan saja, tidak menyentuh RBAC).
--             Migration ini hanya mengganti label & sort_order supaya
--             konsisten dengan urutan menu Inventarisasi Pohon lainnya
--             (Kondisi Pohon=40, Stok Bibit=50).
-- Aman dijalankan ulang (idempotent).
-- =====================================================================

UPDATE `menus`
SET `label` = 'Monitoring Pohon',
    `sort_order` = 55
WHERE `code` = 'monitoring';
