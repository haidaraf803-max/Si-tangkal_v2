<?php

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once '../config/rbac_guard.php';
require_once __DIR__ . '/../../Admin/core/PemeliharaanModels.php';

try {
    apiRequirePermission($pdo, 'pemakaian_bbm', 'delete');

    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'id wajib diisi']);
        exit;
    }

    $model = new BbmModel($pdo);
    echo json_encode(['success' => $model->delete($id)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
}
