<?php

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/roles/update] ' . $errstr);
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

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan, gunakan POST/PUT']);
    exit;
}

try {
    apiRequirePermission($pdo, 'roles', 'edit');

    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id   = (int) ($body['id'] ?? 0);
    $name = trim($body['name'] ?? '');
    $desc = trim($body['description'] ?? '');

    if ($id <= 0 || $name === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'id dan name wajib diisi']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $desc, $id]);

    echo json_encode(['success' => true, 'message' => 'Role berhasil diperbarui']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
