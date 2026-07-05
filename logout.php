<?php
/**
 * logout.php
 * Logout terpusat lewat class Auth — membersihkan SEMUA bentuk
 * session (Admin & publik) sekaligus, lalu kembali ke beranda.
 */
require_once __DIR__ . '/config.php';

Auth::logout();

header('Location: index.php');
exit;
