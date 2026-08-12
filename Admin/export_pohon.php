<?php
/**
 * Admin/export_pohon.php
 * Export data pohon ke CSV.
 * - mode=all   -> export SELURUH data pohon
 * - mode=range -> export berdasarkan rentang TAHUN TANAM (tahun_awal / tahun_akhir)
 *
 * Catatan: tabel `pohon` tidak memiliki kolom tanggal input, sehingga
 * filter yang tersedia menggunakan tahun tanam sebagai pendekatan tanggal.
 */
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/PohonModel.php';

$model = new PohonModel($config);

$mode       = $_GET['mode'] ?? 'all';
$tahunAwal  = trim($_GET['tahun_awal'] ?? '');
$tahunAkhir = trim($_GET['tahun_akhir'] ?? '');

if ($mode !== 'range') {
    $tahunAwal  = '';
    $tahunAkhir = '';
}

$data = $model->getForExport($tahunAwal, $tahunAkhir);

// ===== Nama file =====
if ($mode === 'range' && ($tahunAwal !== '' || $tahunAkhir !== '')) {
    $suffix = '_tahun-' . ($tahunAwal !== '' ? $tahunAwal : 'awal') . '-sd-' . ($tahunAkhir !== '' ? $tahunAkhir : 'akhir');
} else {
    $suffix = '_semua-data';
}
$filename = 'data_pohon' . $suffix . '_' . date('Ymd_His') . '.csv';

// Bersihkan output buffer sebelum kirim file (hindari karakter/whitespace nyasar merusak CSV)
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
    'Nama Lokal',
    'Nama Latin',
    'Family',
    'Tahun Tanam',
    'Umur Pohon',
    'Habitus',
    'Status Kelangkaan',
    'Volume',
    'Kelas Awet',
    'Kelas Kuat',
    'Berat Jenis',
    'Kondisi Kesehatan',
    'Serapan CO2',
    'Produksi O2',
    'Nama Jalan',
    'Kelurahan',
    'Kecamatan',
    'Latitude',
    'Longitude',
    'Keterangan',
]);

$no = 1;
foreach ($data as $row) {
    fputcsv($output, [
        $no++,
        $row['nama_lokal'] ?? '',
        $row['nama_latin'] ?? '',
        $row['family'] ?? '',
        $row['tahun_tanam'] ?? '',
        $row['umur_pohon'] ?? '',
        $row['habitus'] ?? '',
        $row['status_kel'] ?? '',
        $row['volume'] ?? '',
        $row['kelas_awet'] ?? '',
        $row['kelas_kuat'] ?? '',
        $row['berat_jenis'] ?? '',
        $row['kesehatan'] ?? '',
        $row['serapan_co'] ?? '',
        $row['produksi_o'] ?? '',
        $row['nama_jalan'] ?? '',
        $row['kelurahan'] ?? '',
        $row['kecamatan'] ?? '',
        $row['koordinat_y'] ?? '', // latitude
        $row['koordinat_x'] ?? '', // longitude
        $row['keterangan'] ?? '',
    ]);
}

fclose($output);
exit;