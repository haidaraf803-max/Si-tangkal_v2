<?php
/**
 * api/tree-detail.php
 * --------------------
 * Detail 1 pohon (dipakai oleh modal "Lihat Detail" di peta — lihat
 * openTreeDetail() di assets/js/ui.js) — mengembalikan field yang sama
 * lengkapnya dengan yang dipakai pages/tree-detail.php (halaman detail
 * versi penuh), supaya isi popup & halaman detail selalu konsisten karena
 * sama-sama lewat TreeRepository::find().
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/TreeRepository.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
$tree = $id > 0 ? TreeRepository::find($id) : null;

if (!$tree) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Data pohon tidak ditemukan',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'tree' => $tree,
], JSON_UNESCAPED_UNICODE);
