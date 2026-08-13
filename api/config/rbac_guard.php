<?php
/**
 * api/config/rbac_guard.php
 * ---------------------------------------------------------
 * Guard endpoint API yang butuh izin RBAC granular, dipakai
 * setelah api/config/auth.php (login check). Sama seperti
 * Admin/core/Rbac.php tapi keluarannya JSON, bukan HTML —
 * konsisten dengan gaya seluruh folder api/.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/auth.php'; // sudah include config.php + cek login, set $admin & $user_id
require_once __DIR__ . '/../../Admin/core/Rbac.php';

/**
 * apiRequirePermission('roles', 'edit') -> exit dgn 403 JSON jika tidak diizinkan
 */
function apiRequirePermission(PDO $pdo, string $menuCode, string $action = 'view'): void
{
    if (Rbac::can($pdo, $menuCode, $action)) {
        return;
    }

    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => "Peran Anda tidak memiliki izin '{$action}' pada menu '{$menuCode}'.",
    ]);
    exit;
}
