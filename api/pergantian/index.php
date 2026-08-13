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
    $model = new PergantianModel($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        apiRequirePermission($pdo, 'pergantian_pohon', 'view');
        $data = isset($_GET['id']) ? $model->getById((int) $_GET['id']) : $model->getAll();
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        apiRequirePermission($pdo, 'pergantian_pohon', 'create');
        $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $userId = (int) ($_SESSION['admin']['UserId'] ?? 0);

        if (empty($body['jenis_pohon']) || empty($body['diameter_cm']) || empty($body['jumlah_pohon'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'jenis_pohon, diameter_cm, dan jumlah_pohon wajib diisi']);
            exit;
        }

        $newId = $model->create($body, $userId);
        if (!$newId) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perhitungan']);
            exit;
        }

        $saved = $model->getById($newId);
        echo json_encode(['success' => true, 'data' => $saved], JSON_UNESCAPED_UNICODE);
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
