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
require_once __DIR__ . '/../../Admin/core/PemeliharaanModels.php';

try {
    $model = new BbmModel($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        apiRequirePermission($pdo, 'pemakaian_bbm', 'view');
        $data = isset($_GET['id']) ? $model->getById((int) $_GET['id']) : $model->getAll();
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        apiRequirePermission($pdo, 'pemakaian_bbm', 'create');
        $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $userId = (int) ($_SESSION['admin']['UserId'] ?? 0);

        if (empty($body['tanggal']) || empty($body['jenis_bbm']) || empty($body['jumlah_liter'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'tanggal, jenis_bbm, dan jumlah_liter wajib diisi']);
            exit;
        }

        $newId = $model->create($body, $userId);
        echo json_encode(['success' => (bool) $newId, 'data' => ['id' => $newId]]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Gunakan GET/POST']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
