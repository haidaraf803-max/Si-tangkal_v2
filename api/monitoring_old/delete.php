<?php

header('Content-Type: application/json');

session_start();

require_once '../config/database.php';

try {

    // =========================
    // AUTH CHECK
    // =========================
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Anda belum login'
        ]);
        exit;
    }

    // =========================
    // VALIDASI ID
    // =========================
    if (empty($_GET['id'])) {
        throw new Exception('ID wajib diisi');
    }

    $id = $_GET['id'];

    // =========================
    // CEK DATA EXIST
    // =========================
    $check = $pdo->prepare("SELECT id FROM monitoring WHERE id = ?");
    $check->execute([$id]);

    if (!$check->fetch()) {
        throw new Exception('Data monitoring tidak ditemukan');
    }

    // =========================
    // START TRANSACTION
    // =========================
    $pdo->beginTransaction();

    // =========================
    // AMBIL MEDIA
    // =========================
    $stmtMedia = $pdo->prepare("
        SELECT *
        FROM monitoring_media
        WHERE monitoring_id = ?
    ");

    $stmtMedia->execute([$id]);

    $mediaList = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // HAPUS FILE FISIK
    // =========================
    foreach ($mediaList as $media) {

        $fullPath = dirname(__DIR__, 2) . '/' . $media['file_path'];

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    // =========================
    // HAPUS MEDIA DB
    // =========================
    $stmtDeleteMedia = $pdo->prepare("
        DELETE FROM monitoring_media
        WHERE monitoring_id = ?
    ");

    $stmtDeleteMedia->execute([$id]);

    // =========================
    // HAPUS DATA MONITORING
    // =========================
    $stmtDeleteMonitoring = $pdo->prepare("
        DELETE FROM monitoring
        WHERE id = ?
    ");

    $stmtDeleteMonitoring->execute([$id]);

    // =========================
    // COMMIT
    // =========================
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Monitoring berhasil dihapus'
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}