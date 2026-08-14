<?php
/**
 * api/pohon_pending/validate.php
 * ---------------------------------------------------------
 * Validasi 1 baris data pending. Jika lolos aturan validasi
 * (lihat PohonPendingModel::validateRow), baris dipindahkan ke
 * tabel `pohon` dan status ditandai 'valid'. Jika tidak lolos,
 * baris ditandai 'invalid' beserta alasannya — TIDAK dipindahkan.
 *
 * Method: POST
 * Body (JSON atau form): { "id": <id_pending> }
 * ---------------------------------------------------------
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/pohon_pending/validate] ' . $errstr);
    return true;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.']);
    }
});

require_once '../config/rbac_guard.php';
require_once __DIR__ . '/../../Admin/core/PohonModel.php';
require_once __DIR__ . '/../../Admin/core/PohonPendingModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan, gunakan POST']);
    exit;
}

try {
    // Validasi & pemindahan data setara dengan hak akses "edit" pada menu pohon.
    apiRequirePermission($pdo, 'pohon', 'edit');

    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id   = (int) ($body['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('id wajib diisi');
    }

    $pendingModel = new PohonPendingModel($pdo);
    $pohonModel   = new PohonModel($pdo);

    $result = $pendingModel->approve($id, $pohonModel, $user_id);

    http_response_code($result['success'] ? 200 : 422);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
