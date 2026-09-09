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
    echo json_encode(['success' => false, 'message' => 'Gunakan POST (multipart/form-data)']);
    exit;
}

try {
    apiRequirePermission($pdo, 'pengajuan', 'edit');

    $roleCode = Rbac::currentRoleCode($pdo);
    if (!Rbac::isSuperadmin($pdo) && $roleCode !== 'tim_tangkas') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Hanya Tim Tangkas yang boleh mengunggah dokumentasi eksekusi']);
        exit;
    }

    $id     = (int) ($_POST['id'] ?? 0);
    $userId = (int) ($_SESSION['admin']['UserId'] ?? 0);

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'id wajib diisi']);
        exit;
    }

    $uploadDir = __DIR__ . '/../../images/';
    $fotoSesudahName = '';

    // Tim Tangkas hanya mengunggah foto SESUDAH penanganan.
    // Foto "sebelum" sudah wajib diisi pemohon saat membuat pengajuan.
    if (!empty($_FILES['foto_sesudah']['name'])) {
        $fotoSesudahName = 'sesudah_' . $id . '_' . time() . '_' . basename($_FILES['foto_sesudah']['name']);
        move_uploaded_file($_FILES['foto_sesudah']['tmp_name'], $uploadDir . $fotoSesudahName);
    }

    $model = new PengajuanModel($pdo);
    $ok = $model->eksekusi($id, $userId, $fotoSesudahName);

    echo json_encode(['success' => $ok, 'message' => $ok ? 'Dokumentasi eksekusi tersimpan, pengajuan selesai' : 'Gagal menyimpan dokumentasi']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
