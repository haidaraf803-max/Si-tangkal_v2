<?php
/**
 * api/pohon_pending/read.php
 * ---------------------------------------------------------
 * GET ?id=<id>            -> detail 1 baris pending
 * GET ?status=pending      -> daftar baris dengan status tertentu
 *                             (pending|valid|invalid)
 * GET (tanpa parameter)    -> seluruh baris pending, terbaru dulu
 * ---------------------------------------------------------
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/pohon_pending/read] ' . $errstr);
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

try {
    apiRequirePermission($pdo, 'pohon', 'view');

    $model = new PohonPendingModel($pdo);

    if (!empty($_GET['id'])) {
        $row = $model->getById((int) $_GET['id']);
        if (!$row) {
            throw new Exception('Data pending tidak ditemukan');
        }
        echo json_encode(['success' => true, 'data' => $row]);
        exit;
    }

    $status = trim($_GET['status'] ?? '');
    $data   = $model->getAll($status !== '' ? $status : null);

    echo json_encode([
        'success' => true,
        'total'   => count($data),
        'data'    => $data,
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
