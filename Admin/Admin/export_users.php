<?php
/**
 * Admin/export_users.php
 * Export data pengguna admin ke CSV.
 * Kolom password TIDAK disertakan demi keamanan.
 * Mendukung parameter opsional `cari` agar hasil export mengikuti
 * pencarian yang sedang aktif di halaman users.php.
 */
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/UserModel.php';

$model   = new UserModel($config);
$keyword = trim($_GET['cari'] ?? '');

$data = $model->getAll($keyword);

// ===== Nama file =====
$suffix   = $keyword !== '' ? '_cari-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $keyword) : '_semua-data';
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
