<?php
/**
 * api/auth/login.php
 * ---------------------------------------------------------
 * Login untuk konsumen API (mis. app monitoring). Memakai
 * class Auth yang sama dengan seluruh aplikasi (login.php,
 * Admin, dsb) supaya satu akun & satu session konsisten
 * di mana saja dipakai.
 * ---------------------------------------------------------
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';

try {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        throw new Exception('Username dan password wajib diisi');
    }

    if (!Auth::attempt($username, $password)) {
        throw new Exception('Username atau password salah');
    }

    $admin = Auth::admin();

    // Alias supaya endpoint lama yang membaca $_SESSION['user_id'] tetap jalan
    $_SESSION['user_id']  = $admin['UserId'] ?? null;
    $_SESSION['nama']     = $admin['Name'] ?? '';
    $_SESSION['username'] = $admin['Username'] ?? $username;

    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil',
        'user' => [
            'id'       => $admin['UserId'] ?? null,
            'nama'     => $admin['Name'] ?? '',
            'username' => $admin['Username'] ?? $username,
        ],
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
