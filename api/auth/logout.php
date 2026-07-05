<?php
/**
 * api/auth/logout.php
 * Logout terpusat lewat class Auth (session yang sama dipakai
 * di seluruh aplikasi).
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';

Auth::logout();

echo json_encode([
    'success' => true,
    'message' => 'Logout berhasil',
]);
