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
require_once __DIR__ . '/../../Admin/core/PergantianPohonModel.php';

try {
    $model = new TarifModel($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        apiRequirePermission($pdo, 'pergantian_pohon', 'view');
        $data = isset($_GET['id']) ? $model->getById((int) $_GET['id']) : $model->getAll();
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        apiRequirePermission($pdo, 'pergantian_pohon', 'edit');
        $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        if (empty($body['jenis_pohon']) || !isset($body['harga_per_cm'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'jenis_pohon dan harga_per_cm wajib diisi']);
            exit;
        }

        $ok = $model->create(
            $body['jenis_pohon'],
            (float) ($body['diameter_min'] ?? 0),
            (float) ($body['diameter_max'] ?? 999),
            (float) $body['harga_per_cm'],
            trim($body['keterangan'] ?? '')
        );
        echo json_encode(['success' => $ok]);
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
