<?php
/**
 * api/pohon_pending/reject.php
 * ---------------------------------------------------------
 * Tolak 1 baris data pending secara manual (terlepas dari hasil
 * validasi otomatis). Baris ditandai status='invalid'.
 *
 * Method: POST
 * Body (JSON atau form): { "id": <id_pending>, "catatan": "alasan" }
 * ---------------------------------------------------------
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/pohon_pending/reject] ' . $errstr);
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
require_once __DIR__ . '/../../Admin/core/PohonPendingModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan, gunakan POST']);
    exit;
}

try {
    apiRequirePermission($pdo, 'pohon', 'edit');

    $body    = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id      = (int) ($body['id'] ?? 0);
    $catatan = trim($body['catatan'] ?? '');

    if ($id <= 0) {
        throw new Exception('id wajib diisi');
    }

    $model = new PohonPendingModel($pdo);
    $ok    = $model->reject($id, $catatan, $user_id);

    if (!$ok) {
        throw new Exception('Data tidak ditemukan atau sudah divalidasi sebelumnya');
    }

    echo json_encode(['success' => true, 'message' => 'Data pending berhasil ditolak.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
