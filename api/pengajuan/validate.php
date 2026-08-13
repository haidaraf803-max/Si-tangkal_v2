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
require_once __DIR__ . '/../../Admin/core/PengajuanModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Gunakan POST']);
    exit;
}

try {
    apiRequirePermission($pdo, 'pengajuan', 'edit');

    $roleCode = Rbac::currentRoleCode($pdo);
    if (!Rbac::isSuperadmin($pdo) && $roleCode !== 'validator') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Hanya Validator yang boleh melakukan validasi']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id      = (int) ($body['id'] ?? 0);
    $approve = ($body['keputusan'] ?? '') === 'setuju';
    $catatan = trim($body['catatan_validasi'] ?? '');
    $userId  = (int) ($_SESSION['admin']['UserId'] ?? 0);

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'id wajib diisi']);
        exit;
    }

    $model = new PengajuanModel($pdo);
    $ok = $model->validate($id, $userId, $approve, $catatan);

    echo json_encode(['success' => $ok, 'message' => $ok ? 'Validasi tersimpan' : 'Gagal menyimpan validasi']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
