<?php

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/roles/permissions] ' . $errstr);
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
    $roleId = (int) ($_GET['role_id'] ?? ($_POST['role_id'] ?? 0));

    if ($roleId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'role_id wajib diisi']);
        exit;
    }

    // ==========================================
    // GET -> ambil matriks izin untuk 1 role
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        apiRequirePermission($pdo, 'roles', 'view');

        $stmt = $pdo->prepare(
            "SELECT m.id AS menu_id, m.code, m.label, m.icon,
                    COALESCE(rma.can_view, 0)   AS can_view,
                    COALESCE(rma.can_create, 0) AS can_create,
                    COALESCE(rma.can_edit, 0)   AS can_edit,
                    COALESCE(rma.can_delete, 0) AS can_delete
             FROM menus m
             LEFT JOIN role_menu_access rma ON rma.menu_id = m.id AND rma.role_id = ?
             ORDER BY m.sort_order"
        );
        $stmt->execute([$roleId]);

        echo json_encode([
            'success' => true,
            'role_id' => $roleId,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==========================================
    // POST -> simpan matriks izin (replace-all utk role ini)
    // Body JSON contoh:
    // { "role_id": 4, "permissions": { "2": {"view":1,"create":1,"edit":1,"delete":0}, ... } }
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        apiRequirePermission($pdo, 'roles', 'edit');

        $body = json_decode(file_get_contents('php://input'), true);
        $permissions = $body['permissions'] ?? ($_POST['perm'] ?? []);

        if (!is_array($permissions) || empty($permissions)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'permissions wajib diisi']);
            exit;
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            "INSERT INTO role_menu_access (role_id, menu_id, can_view, can_create, can_edit, can_delete)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_create = VALUES(can_create),
                                      can_edit = VALUES(can_edit), can_delete = VALUES(can_delete)"
        );

        foreach ($permissions as $menuId => $flags) {
            $stmt->execute([
                $roleId,
                (int) $menuId,
                !empty($flags['view']) ? 1 : 0,
                !empty($flags['create']) ? 1 : 0,
                !empty($flags['edit']) ? 1 : 0,
                !empty($flags['delete']) ? 1 : 0,
            ]);
        }
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Matriks izin berhasil disimpan']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan, gunakan GET/POST']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
