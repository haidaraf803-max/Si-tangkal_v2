<?php
/**
 * api/pohon_pending/delete.php
 * ---------------------------------------------------------
 * Hapus 1 baris data pending (staging), tidak menyentuh tabel pohon.
 *
 * Method: GET/POST ?id=<id_pending>
 * ---------------------------------------------------------
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/pohon_pending/delete] ' . $errstr);
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
    apiRequirePermission($pdo, 'pohon', 'delete');

    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('id wajib diisi');
    }

    $model = new PohonPendingModel($pdo);
    if (!$model->getById($id)) {
        throw new Exception('Data pending tidak ditemukan');
    }

    $model->delete($id);

    echo json_encode(['success' => true, 'message' => 'Data pending berhasil dihapus.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
