<?php
/**
 * Admin/export_pengajuan.php
 * Export data pengajuan (pemangkasan/penebangan pohon) ke CSV.
 * Mendukung parameter opsional `cari` agar hasil export mengikuti
 * pencarian yang sedang aktif di halaman pengajuan.php.
 */
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/PengajuanModel.php';

$model   = new PengajuanModel($config);
$keyword = trim($_GET['cari'] ?? '');

$data = $keyword !== '' ? $model->search($keyword) : $model->getAll();

// ===== Nama file =====
$suffix   = $keyword !== '' ? '_cari-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $keyword) : '_semua-data';
$filename = 'data_pengajuan' . $suffix . '_' . date('Ymd_His') . '.csv';

// Bersihkan output buffer sebelum kirim file
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM UTF-8 supaya Excel menampilkan karakter dengan benar
fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header kolom
fputcsv($output, [
    'No',
    'No Surat',
    'Nama Pemohon',
    'Nomor Telepon',
    'Lokasi Pohon',
    'Disposisi Surat',
    'Survey Pohon',
    'Tanggal Penanganan',
    'Keterangan',
    'Status',
]);

$no = 1;
foreach ($data as $row) {
    $isDone = (strtolower($row['Keterangan'] ?? '') === 'sudah');
    fputcsv($output, [
        $no++,
        $row['No_Surat'] ?? '',
        $row['Nama_Pemohon'] ?? '',
        $row['Nomor_Telepon'] ?? '',
        $row['Lokasi_Pohon'] ?? '',
        $row['Disposisi_Surat'] ?? '',
        $row['Survey_Pohon'] ?? '',
        $row['Tanggal_Penanganan'] ?? '',
        $row['Keterangan'] ?? '',
        $isDone ? 'Sudah' : 'Belum',
    ]);
}

fclose($output);
exit;
