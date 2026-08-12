<?php
/**
 * Admin/export_permohonan_bibit.php
 * Export data permohonan bibit tanaman ke CSV.
 * - mode=all   -> export SELURUH data permohonan bibit
 * - mode=range -> export berdasarkan rentang TANGGAL PERMOHONAN
 *                 (tanggal_awal / tanggal_akhir)
 */
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');

$mode     = $_GET['mode'] ?? 'all';
$tglAwal  = trim($_GET['tanggal_awal'] ?? '');
$tglAkhir = trim($_GET['tanggal_akhir'] ?? '');

if ($mode !== 'range') {
    $tglAwal  = '';
    $tglAkhir = '';
}

try {
    $sql        = "SELECT * FROM permohonan_bibit";
    $conditions = [];
    $params     = [];

    if ($tglAwal !== '' && $tglAkhir !== '') {
        $conditions[] = "DATE(tanggal_permohonan) BETWEEN :tgl_awal AND :tgl_akhir";
        $params[':tgl_awal']  = $tglAwal;
        $params[':tgl_akhir'] = $tglAkhir;
    } elseif ($tglAwal !== '') {
        $conditions[] = "DATE(tanggal_permohonan) >= :tgl_awal";
        $params[':tgl_awal'] = $tglAwal;
    } elseif ($tglAkhir !== '') {
        $conditions[] = "DATE(tanggal_permohonan) <= :tgl_akhir";
        $params[':tgl_akhir'] = $tglAkhir;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " ORDER BY tanggal_permohonan DESC, id_bibit DESC";

    $stmt = $config->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data = [];
}

// ===== Nama file =====
if ($mode === 'range' && ($tglAwal !== '' || $tglAkhir !== '')) {
    $suffix = '_' . ($tglAwal !== '' ? $tglAwal : 'awal') . '_sd_' . ($tglAkhir !== '' ? $tglAkhir : 'akhir');
} else {
    $suffix = '_semua-data';
}
$filename = 'data_permohonan_bibit' . $suffix . '_' . date('Ymd_His') . '.csv';

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
    'Nama Pemohon',
    'No Telepon',
    'Jenis Tanaman',
    'Jumlah',
    'Lokasi Tanam',
    'Tanggal Permohonan',
    'Status',
    'Keterangan',
]);

$no = 1;
foreach ($data as $row) {
    fputcsv($output, [
        $no++,
        $row['nama_pemohon'] ?? '',
        $row['nomor_telepon'] ?? '',
        $row['jenis_tanaman'] ?? '',
        $row['jumlah_tanaman'] ?? '',
        $row['lokasi_nanam'] ?? '',
        $row['tanggal_permohonan'] ?? '',
        $row['status_permohonan'] ?? 'Belum',
        $row['keterangan'] ?? '',
    ]);
}

fclose($output);
exit;
