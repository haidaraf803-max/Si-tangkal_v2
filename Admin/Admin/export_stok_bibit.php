<?php
/**
 * Admin/export_stok_bibit.php
 * Export data stok bibit tanaman ke CSV.
 * - filter_sumber (opsional) -> APBD / Pembibitan Mandiri / Hibah
 */
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');

$filterSumber = $_GET['filter_sumber'] ?? '';

try {
    $query  = "SELECT * FROM stok_bibit";
    $params = [];
    if (!empty($filterSumber) && $filterSumber !== 'Semua Kategori') {
        $query .= " WHERE sumber_bibit = :sumber";
        $params[':sumber'] = $filterSumber;
    }
    $query .= " ORDER BY sumber_bibit ASC, jenis_tanaman ASC";

    $stmt = $config->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data = [];
}

// ===== Nama file =====
$suffix   = (!empty($filterSumber) && $filterSumber !== 'Semua Kategori')
    ? '_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $filterSumber)
    : '_semua-data';
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
