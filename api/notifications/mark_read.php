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

require_once '../config/auth.php';
require_once __DIR__ . '/../../Admin/core/Rbac.php';
require_once __DIR__ . '/../../Admin/core/NotificationModel.php';

try {
    $model  = new NotificationModel($pdo);
    $userId = (int) ($_SESSION['admin']['UserId'] ?? 0);
    $roleId = Rbac::currentRoleId($pdo);

    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if (!empty($body['all'])) {
        $ok = $model->markAllRead($userId, $roleId);
    } else {
        $id = (int) ($body['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'id atau all wajib diisi']);
            exit;
        }
        $ok = $model->markRead($id);
    }

    echo json_encode(['success' => $ok]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
