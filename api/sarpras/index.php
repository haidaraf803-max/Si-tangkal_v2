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
    $model = new SarprasModel($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        apiRequirePermission($pdo, 'permintaan_sarpras', 'view');
        $data = isset($_GET['id']) ? $model->getById((int) $_GET['id']) : $model->getAll();
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        // ?action=tanggapi -> ubah status (butuh izin edit)
        if (($body['action'] ?? '') === 'tanggapi') {
            apiRequirePermission($pdo, 'permintaan_sarpras', 'edit');
            $ok = $model->tanggapi((int) ($body['id'] ?? 0), $body['status'] ?? '', trim($body['catatan_admin'] ?? ''));
            echo json_encode(['success' => $ok]);
            exit;
        }

        // default -> ajukan permintaan baru (butuh izin create)
        apiRequirePermission($pdo, 'permintaan_sarpras', 'create');
        $userId = (int) ($_SESSION['admin']['UserId'] ?? 0);

        if (empty($body['tanggal']) || empty($body['nama_barang'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'tanggal dan nama_barang wajib diisi']);
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
