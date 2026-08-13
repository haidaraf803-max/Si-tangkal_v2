<?php

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/roles/delete] ' . $errstr);
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

try {
    apiRequirePermission($pdo, 'roles', 'delete');

    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'id wajib diisi']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Role tidak ditemukan']);
        exit;
    }

    if ((int) $role['is_system'] === 1) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Role bawaan dari dokumen kebutuhan tidak bisa dihapus']);
        exit;
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM t_users WHERE role_id = ?");
    $stmtCount->execute([$id]);
    if ((int) $stmtCount->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Role masih dipakai oleh pengguna lain']);
        exit;
    }

    $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Role berhasil dihapus']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
