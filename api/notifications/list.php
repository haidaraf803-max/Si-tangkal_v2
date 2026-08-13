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

require_once '../config/auth.php'; // cukup login, notifikasi memang khusus milik user ybs
require_once __DIR__ . '/../../Admin/core/Rbac.php';
require_once __DIR__ . '/../../Admin/core/NotificationModel.php';

try {
    $userId = (int) ($_SESSION['admin']['UserId'] ?? 0);
    $roleId = Rbac::currentRoleId($pdo);

    $model = new NotificationModel($pdo);
    $limit = (int) ($_GET['limit'] ?? 10);

    echo json_encode([
        'success' => true,
        'unread'  => $model->countUnread($userId, $roleId),
        'data'    => $model->getForUser($userId, $roleId, $limit),
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
