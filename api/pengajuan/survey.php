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
    if (!Rbac::isSuperadmin($pdo) && $roleCode !== 'petugas_survey') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Hanya Petugas Survey yang boleh mengisi hasil survey']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id      = (int) ($body['id'] ?? 0);
    $hasil   = ($body['hasil_survey'] ?? '') === 'perlu_pemangkasan' ? 'perlu_pemangkasan' : 'tidak_perlu';
    $catatan = trim($body['catatan_survey'] ?? '');
    $userId  = (int) ($_SESSION['admin']['UserId'] ?? 0);

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'id wajib diisi']);
        exit;
    }

    $model = new PengajuanModel($pdo);
    $ok = $model->submitSurvey($id, $userId, $hasil, $catatan);

    echo json_encode(['success' => $ok, 'message' => $ok ? 'Hasil survey tersimpan' : 'Gagal menyimpan hasil survey']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
