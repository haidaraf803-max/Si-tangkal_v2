<?php

/**
 * functions.php
 * Fungsi utilitas umum untuk Admin Dashboard.
 * Load model yang diperlukan sebelum memanggil fungsi ini.
 */

require_once __DIR__ . '/PengajuanModel.php';
require_once __DIR__ . '/PohonModel.php';

/**
 * Ambil 4 statistik utama untuk card dashboard.
 *
 * @param  PDO   $pdo  Koneksi PDO dari config.php
 * @return array {total_pengajuan, belum_proses, pohon_sehat, pohon_mati}
 */
function getDashboardStats(PDO $pdo): array
{
    $pengajuanModel = new PengajuanModel($pdo);
    $pohonModel     = new PohonModel($pdo);

    // Pengajuan Pemangkasan
    $totalPemangkasan = $pengajuanModel->countAll();
    $belumProsesPemangkasan = $pengajuanModel->countBelumProses();

    // Permohonan Bibit
    $stmtBibitTotal = $pdo->query("SELECT COUNT(*) FROM permohonan_bibit");
    $totalBibit = (int)$stmtBibitTotal->fetchColumn();

    $stmtBibitBelum = $pdo->query("SELECT COUNT(*) FROM permohonan_bibit WHERE status_permohonan = 'Belum' OR status_permohonan IS NULL");
    $belumProsesBibit = (int)$stmtBibitBelum->fetchColumn();

    return [
        'total_pengajuan' => $totalPemangkasan + $totalBibit,
        'belum_proses'    => $belumProsesPemangkasan + $belumProsesBibit,
        'pohon_sehat'     => $pohonModel->countByKondisi('Sehat'),
        'pohon_kurang'    => $pohonModel->countByKondisi('Kurang Sehat'),
        'pohon_mati'      => $pohonModel->countByKondisi('Sakit'),
    ];
}
