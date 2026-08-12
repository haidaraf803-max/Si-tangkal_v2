<?php
/**
 * Admin/export_pengajuan.php
 * Export data pengajuan (pemangkasan/penebangan pohon) ke CSV.
 * - mode=all   -> export SELURUH data pengajuan
 * - mode=range -> export berdasarkan rentang TANGGAL DISPOSISI SURAT
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
    $sql        = "SELECT * FROM pengajuan";
    $conditions = [];
    $params     = [];

    if ($tglAwal !== '' && $tglAkhir !== '') {
        $conditions[] = "DATE(Disposisi_Surat) BETWEEN :tgl_awal AND :tgl_akhir";
        $params[':tgl_awal']  = $tglAwal;
        $params[':tgl_akhir'] = $tglAkhir;
    } elseif ($tglAwal !== '') {
        $conditions[] = "DATE(Disposisi_Surat) >= :tgl_awal";
        $params[':tgl_awal'] = $tglAwal;
    } elseif ($tglAkhir !== '') {
        $conditions[] = "DATE(Disposisi_Surat) <= :tgl_akhir";
        $params[':tgl_akhir'] = $tglAkhir;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " ORDER BY Id DESC";

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
    'Tanggal Disposisi Surat',
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
