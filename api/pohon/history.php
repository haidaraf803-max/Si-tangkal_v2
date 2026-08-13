<?php

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.']);
    }
});

require_once '../config/rbac_guard.php';
require_once __DIR__ . '/../../Admin/core/PohonModel.php';

try {
    apiRequirePermission($pdo, 'pohon', 'view');

    $pohonId = (int) ($_GET['pohon_id'] ?? 0);
    if ($pohonId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'pohon_id wajib diisi']);
        exit;
    }

    $model = new PohonModel($pdo);
    $data  = $model->getHistori($pohonId);

    echo json_encode(['success' => true, 'total' => count($data), 'data' => $data], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
