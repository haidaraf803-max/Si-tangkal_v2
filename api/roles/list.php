<?php

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/roles/list] ' . $errstr);
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
    apiRequirePermission($pdo, 'roles', 'view');

    // ?id=<int> -> detail 1 role beserta matriks izin menu
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];

        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Role tidak ditemukan']);
            exit;
        }

        $stmtMatrix = $pdo->prepare(
            "SELECT m.id AS menu_id, m.code, m.label,
                    COALESCE(rma.can_view, 0)   AS can_view,
                    COALESCE(rma.can_create, 0) AS can_create,
                    COALESCE(rma.can_edit, 0)   AS can_edit,
                    COALESCE(rma.can_delete, 0) AS can_delete
             FROM menus m
             LEFT JOIN role_menu_access rma ON rma.menu_id = m.id AND rma.role_id = ?
             ORDER BY m.sort_order"
        );
        $stmtMatrix->execute([$id]);
        $role['permissions'] = $stmtMatrix->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $role], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Daftar semua role
    $stmt = $pdo->query(
        "SELECT r.*, (SELECT COUNT(*) FROM t_users u WHERE u.role_id = r.id) AS total_user
         FROM roles r ORDER BY r.id"
    );
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'total' => count($data), 'data' => $data], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
