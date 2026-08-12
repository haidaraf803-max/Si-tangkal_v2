<?php
/**
 * Admin/export_permohonan_bibit.php
 * Export data permohonan bibit tanaman ke CSV.
 * - mode=all    -> export SELURUH data permohonan bibit
 * - mode=status -> export berdasarkan status_permohonan (Belum/Disetujui/Ditolak)
 */
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');

$mode   = $_GET['mode'] ?? 'all';
$status = trim($_GET['status'] ?? '');
$allowedStatus = ['Belum', 'Disetujui', 'Ditolak'];

try {
    if ($mode === 'status' && in_array($status, $allowedStatus, true)) {
        $stmt = $config->prepare(
            "SELECT * FROM permohonan_bibit WHERE status_permohonan = :status ORDER BY tanggal_permohonan DESC, id_bibit DESC"
        );
        $stmt->execute([':status' => $status]);
    } else {
        $mode = 'all';
        $stmt = $config->prepare("SELECT * FROM permohonan_bibit ORDER BY tanggal_permohonan DESC, id_bibit DESC");
        $stmt->execute();
    }
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data = [];
}

// ===== Nama file =====
$suffix   = ($mode === 'status') ? '_status-' . strtolower($status) : '_semua-data';
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
