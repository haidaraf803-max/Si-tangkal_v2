<?php

header('Content-Type: application/json');

// Lihat catatan di api/monitoring/create.php — API ini harus selalu
// keluar JSON valid, jadi error/warning tidak ditampilkan sebagai HTML.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/monitoring/update] ' . $errstr);
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
            'message' => 'Anda belum login'
        ]);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // =========================
    // VALIDASI ID
    // =========================
    if (empty($_POST['id'])) {
        throw new Exception('ID monitoring wajib diisi');
    }

    $id = $_POST['id'];

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
    // UPDATE DATA
    // =========================
    $stmt = $pdo->prepare("
        UPDATE monitoring
        SET
            pohon_id = ?,
            tanggal_monitoring = ?,
            kesehatan_monitoring = ?,
            catatan = ?,
            user_id = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['pohon_id'] ?? null,
        $_POST['tanggal_monitoring'] ?? date('Y-m-d'),
        $_POST['kesehatan_monitoring'] ?? null,
        $_POST['catatan'] ?? null,
        $user_id,
        $id
    ]);

    // =========================
    // CEK FILE UPLOAD
    // =========================
    $hasFile = isset($_FILES['files']) && !empty($_FILES['files']['name'][0]);

    if ($hasFile) {

        // =========================
        // HAPUS MEDIA LAMA
        // =========================
        $stmtMedia = $pdo->prepare("
            SELECT *
            FROM monitoring_media
            WHERE monitoring_id = ?
        ");

        $stmtMedia->execute([$id]);

        $oldMedia = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

        foreach ($oldMedia as $media) {

            $fullPath = dirname(__DIR__, 2) . '/' . $media['file_path'];

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $pdo->prepare("
            DELETE FROM monitoring_media
            WHERE monitoring_id = ?
        ")->execute([$id]);

        // =========================
        // UPLOAD SETUP
        // =========================
        $todayFolder = date('Y-m-d');

        $fotoDir = "../../media/monitoring/$todayFolder/foto/";
        $videoDir = "../../media/monitoring/$todayFolder/video/";

        if (!file_exists($fotoDir)) mkdir($fotoDir, 0777, true);
        if (!file_exists($videoDir)) mkdir($videoDir, 0777, true);

        $fotoCounter = 1;
        $videoCounter = 1;

        // =========================
        // LOOP FILE
        // =========================
        foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {

            // ERROR CHECK (WAJIB)
            if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload error code: " . $_FILES['files']['error'][$key]);
            }

            $originalName = $_FILES['files']['name'][$key];
            $mimeType = $_FILES['files']['type'][$key];

            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $allowedImages = ['jpg','jpeg','png','webp'];
            $allowedVideos = ['mp4','mov','avi','mkv'];
            $allowedExtensions = array_merge($allowedImages, $allowedVideos);

            if (!in_array($extension, $allowedExtensions)) {
                continue;
            }

            // =========================
            // VIDEO
            // =========================
            if (str_contains($mimeType, 'video')) {

                $mediaType = 'video';

                $newName = date('Ymd_His') . '_' . $videoCounter . '.' . $extension;

                $savePath = $videoDir . $newName;

                $dbPath = "media/monitoring/$todayFolder/video/$newName";

                $videoCounter++;

            }
            // =========================
            // FOTO
            // =========================
            else {

                $mediaType = 'foto';

                $newName = date('Ymd_His') . '_' . $fotoCounter . '.' . $extension;

                $savePath = $fotoDir . $newName;

                $dbPath = "media/monitoring/$todayFolder/foto/$newName";

                $fotoCounter++;
            }

            // =========================
            // MOVE FILE
            // =========================
            if (!move_uploaded_file($tmpName, $savePath)) {
                throw new Exception("Gagal upload file: $originalName");
            }

            // =========================
            // INSERT MEDIA DB
            // =========================
            $stmtMedia = $pdo->prepare("
                INSERT INTO monitoring_media
                (
                    monitoring_id,
                    media_type,
                    file_name,
                    file_path
                )
                VALUES
                (
                    :monitoring_id,
                    :media_type,
                    :file_name,
                    :file_path
                )
            ");

            $stmtMedia->execute([
                ':monitoring_id' => $id,
                ':media_type' => $mediaType,
                ':file_name' => $originalName,
                ':file_path' => $dbPath
            ]);
        }
    }

    // =========================
    // COMMIT
    // =========================
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Monitoring berhasil diperbarui'
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