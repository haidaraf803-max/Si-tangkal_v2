<?php

header('Content-Type: application/json');

// Lihat catatan di api/monitoring/create.php — API ini harus selalu
// keluar JSON valid, jadi error/warning tidak ditampilkan sebagai HTML.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/pohon/delete] ' . $errstr);
    return true;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan pada server.',
        ]);
    }
});

require_once '../config/database.php';

// Session sudah otomatis dimulai oleh config.php (lihat require di atas).

try {

    // =========================
    // AUTH CHECK
    // =========================
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Anda belum login',
        ]);
        exit;
    }

    // =========================
    // VALIDASI ID
    // =========================
    if (empty($_GET['id'])) {
        throw new Exception('ID pohon wajib diisi');
    }

    $id = (int) $_GET['id'];

    // =========================
    // CEK DATA EXIST
    // =========================
    $check = $pdo->prepare("SELECT foto FROM pohon WHERE id = ?");
    $check->execute([$id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        throw new Exception('Data pohon tidak ditemukan');
    }

    // =========================
    // START TRANSACTION
    // =========================
    $pdo->beginTransaction();

    // =========================
    // HAPUS DATA POHON
    // =========================
    $stmt = $pdo->prepare("DELETE FROM pohon WHERE id = ?");
    $stmt->execute([$id]);

    // =========================
    // HAPUS FILE FOTO FISIK
    // =========================
    if (!empty($existing['foto'])) {
        $fullPath = "../../assets/foto/" . $existing['foto'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Data pohon berhasil dihapus',
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
