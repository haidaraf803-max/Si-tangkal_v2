<?php

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/menus/list] ' . $errstr);
    return true;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.']);
    }
});

require_once '../config/auth.php'; // cukup login, semua peran boleh tahu daftar menu miliknya sendiri
require_once __DIR__ . '/../../Admin/core/Rbac.php';

try {
    // ?mine=1 -> hanya menu yang boleh dilihat user yang sedang login
    //            (dipakai membangun navigasi di aplikasi client/mobile)
    if (isset($_GET['mine'])) {
        $menus = Rbac::accessibleMenus($pdo);
        echo json_encode(['success' => true, 'data' => $menus], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Default: daftar semua menu (dipakai halaman Manajemen Role untuk
    // membangun kolom matriks izin). Butuh izin view menu 'roles'.
    if (!Rbac::can($pdo, 'roles', 'view')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin melihat daftar menu']);
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM menus ORDER BY sort_order");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
