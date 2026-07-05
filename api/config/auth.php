<?php
/**
 * api/config/auth.php
 * ---------------------------------------------------------
 * Guard endpoint API yang butuh login. Memakai session &
 * class Auth yang sama dengan seluruh aplikasi (lihat /config.php
 * dan /includes/auth.php) — bukan pengecekan session terpisah.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../../config.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
    ]);
    exit;
}

$admin   = Auth::admin();
$user_id = $admin['UserId'] ?? null;
