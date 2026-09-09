<?php
/**
 * template_import_pohon.php
 * Menyediakan file template (CSV, kompatibel dibuka/diedit di Excel)
 * untuk fitur "Import Data Pohon" pada Admin/pohon.php (poin 5a).
 */
require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'pohon', 'view');
require_once 'core/PohonImportHelper.php';

$filename = 'template_import_pohon_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// BOM supaya Excel langsung mengenali encoding UTF-8 (karakter °, é, dsb aman).
fwrite($out, "\xEF\xBB\xBF");

// Header pakai key internal (nama_lokal, dst) — inilah yang dicocokkan
// importer, jadi baris ini JANGAN dihapus/diubah saat diisi.
fputcsv($out, array_keys(PohonImportHelper::TEMPLATE_COLUMNS));

// Baris contoh supaya format lebih jelas bagi pengisi data.
fputcsv($out, [
    'Pohon Mahoni Jl. Contoh',
    'Swietenia macrophylla',
    'Meliaceae',
    '2018',
    'Pohon',
    'Milik Pemerintah',
    '1.25',
    'II',
    'II',
    '560',
    'Sehat',
    '120.5',
    '85.2',
    'Jl. Contoh No. 1',
    'Contoh Kelurahan',
    'Contoh Kecamatan',
    '107.541234',
    '-6.889123',
    'Contoh keterangan (opsional)',
    '7',
]);

fclose($out);
exit;