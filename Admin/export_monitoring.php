<?php
/**
 * Admin/export_monitoring.php
 * Export data monitoring ke CSV.
 * - mode=all   -> export SELURUH data monitoring
 * - mode=range -> export berdasarkan rentang tanggal_monitoring (tanggal_awal / tanggal_akhir)
 */
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/MonitoringModel.php';

$model = new MonitoringModel($config);

$mode      = $_GET['mode'] ?? 'all';
$tglAwal   = trim($_GET['tanggal_awal'] ?? '');
$tglAkhir  = trim($_GET['tanggal_akhir'] ?? '');

if ($mode !== 'range') {
    $tglAwal  = '';
    $tglAkhir = '';
}

$data = $model->getForExport($tglAwal, $tglAkhir);

// ===== Nama file =====
if ($mode === 'range' && ($tglAwal !== '' || $tglAkhir !== '')) {
    $suffix = '_' . ($tglAwal !== '' ? $tglAwal : 'awal') . '_sd_' . ($tglAkhir !== '' ? $tglAkhir : 'akhir');
} else {
    $suffix = '_semua-data';
}
$filename = 'data_monitoring' . $suffix . '_' . date('Ymd_His') . '.csv';

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
    'Tanggal Monitoring',
    'Nama Pohon',
    'Nama Latin',
    'Nama Jalan',
    'Kelurahan',
    'Kecamatan',
    'Kondisi Hasil Monitoring',
    'Catatan',
    'Tinggi Pohon (m)',
    'Diameter Batang / DBH (cm)',
    'Lebar Tajuk (m)',
    'Jenis Gangguan',
    'Tingkat Keparahan',
    'Rekomendasi Tindakan',
    'Status Tindak Lanjut',
    'Latitude Survei',
    'Longitude Survei',
    'Petugas',
]);

$no = 1;
foreach ($data as $row) {
    fputcsv($output, [
        $no++,
        $row['tanggal_monitoring'] ?? '',
        $row['nama_lokal'] ?? '',
        $row['nama_latin'] ?? '',
        $row['nama_jalan'] ?? '',
        $row['kelurahan'] ?? '',
        $row['kecamatan'] ?? '',
        $row['kesehatan_monitoring'] ?? '',
        $row['catatan'] ?? '',
        $row['tinggi_pohon'] ?? '',
        $row['diameter_batang'] ?? '',
        $row['lebar_tajuk'] ?? '',
        $row['jenis_gangguan'] ?? '',
        $row['tingkat_keparahan'] ?? '',
        $row['rekomendasi_tindakan'] ?? '',
        $row['status_tindak_lanjut'] ?? '',
        $row['latitude'] ?? '',
        $row['longitude'] ?? '',
        $row['petugas_name'] ?? ($row['petugas_username'] ?? ''),
    ]);
}

fclose($output);
exit;