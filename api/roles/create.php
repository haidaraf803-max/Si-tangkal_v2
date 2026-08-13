<?php

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/roles/create] ' . $errstr);
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan, gunakan POST']);
    exit;
}

try {
    apiRequirePermission($pdo, 'roles', 'create');

    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $code = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $body['code'] ?? '')));
    $name = trim($body['name'] ?? '');
    $desc = trim($body['description'] ?? '');

    if ($code === '' || $name === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'code dan name wajib diisi']);
        exit;
    }

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE code = ?");
    $stmtCheck->execute([$code]);
    if ((int) $stmtCheck->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Kode role '{$code}' sudah dipakai"]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO roles (code, name, description, is_system) VALUES (?, ?, ?, 0)");
    $stmt->execute([$code, $name, $desc]);

    echo json_encode([
        'success' => true,
        'message' => 'Role berhasil ditambahkan',
        'data' => ['id' => (int) $pdo->lastInsertId(), 'code' => $code, 'name' => $name, 'description' => $desc],
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
