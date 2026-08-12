<?php
/**
 * Admin/export_users.php
 * Export data pengguna admin ke CSV.
 * Kolom password TIDAK disertakan demi keamanan.
 * - mode=all   -> export SELURUH data pengguna
 * - mode=range -> export berdasarkan rentang TANGGAL DIBUAT (CreatedDate)
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
    $sql        = "SELECT * FROM t_users";
    $conditions = [];
    $params     = [];

    if ($tglAwal !== '' && $tglAkhir !== '') {
        $conditions[] = "DATE(CreatedDate) BETWEEN :tgl_awal AND :tgl_akhir";
        $params[':tgl_awal']  = $tglAwal;
        $params[':tgl_akhir'] = $tglAkhir;
    } elseif ($tglAwal !== '') {
        $conditions[] = "DATE(CreatedDate) >= :tgl_awal";
        $params[':tgl_awal'] = $tglAwal;
    } elseif ($tglAkhir !== '') {
        $conditions[] = "DATE(CreatedDate) <= :tgl_akhir";
        $params[':tgl_akhir'] = $tglAkhir;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " ORDER BY UserId DESC";

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
$filename = 'data_users' . $suffix . '_' . date('Ymd_His') . '.csv';

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

// Header kolom (TANPA kolom password)
fputcsv($output, [
    'No',
    'Username',
    'Nama',
    'Email',
    'Tipe / Role',
    'Tanggal Dibuat',
]);

$no = 1;
foreach ($data as $row) {
    fputcsv($output, [
        $no++,
        $row['Username'] ?? '',
        $row['Name'] ?? '',
        $row['Email'] ?? '',
        $row['Type'] ?? '',
        $row['CreatedDate'] ?? '',
    ]);
}

fclose($output);
exit;
