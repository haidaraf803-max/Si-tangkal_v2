<?php
/**
 * Admin/export_stok_bibit.php
 * Export data stok bibit tanaman ke CSV.
 * - mode=all   -> export SELURUH data stok bibit
 * - mode=range -> export berdasarkan rentang TANGGAL UPDATE
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
    $sql        = "SELECT * FROM stok_bibit";
    $conditions = [];
    $params     = [];

    if ($tglAwal !== '' && $tglAkhir !== '') {
        $conditions[] = "DATE(tanggal_update) BETWEEN :tgl_awal AND :tgl_akhir";
        $params[':tgl_awal']  = $tglAwal;
        $params[':tgl_akhir'] = $tglAkhir;
    } elseif ($tglAwal !== '') {
        $conditions[] = "DATE(tanggal_update) >= :tgl_awal";
        $params[':tgl_awal'] = $tglAwal;
    } elseif ($tglAkhir !== '') {
        $conditions[] = "DATE(tanggal_update) <= :tgl_akhir";
        $params[':tgl_akhir'] = $tglAkhir;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " ORDER BY sumber_bibit ASC, jenis_tanaman ASC";

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
$filename = 'data_stok_bibit' . $suffix . '_' . date('Ymd_His') . '.csv';

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
    'Jenis Tanaman',
    'Sumber Bibit',
    'Jumlah Tersedia',
    'Tanggal Update',
]);

$no = 1;
foreach ($data as $row) {
    $tglUpdate = !empty($row['tanggal_update']) ? date('d-m-Y', strtotime($row['tanggal_update'])) : '';
    fputcsv($output, [
        $no++,
        $row['jenis_tanaman'] ?? '',
        $row['sumber_bibit'] ?? '',
        $row['jumlah_tersedia'] ?? '',
        $tglUpdate,
    ]);
}

fclose($output);
exit;
