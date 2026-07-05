<?php

header('Content-Type: application/json');

require_once '../config/database.php';

// sementara hardcode dulu untuk testing
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Anda belum login'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

$user_id = $_SESSION['user_id'];
try {

    $pdo->beginTransaction();

    // Validasi sederhana
    if (empty($_POST['pohon_id'])) {
        throw new Exception('Pohon wajib dipilih');
    }

    // Simpan monitoring
    $stmt = $pdo->prepare("
        INSERT INTO monitoring
        (
            pohon_id,
            user_id,
            tanggal_monitoring,
            kesehatan_monitoring,
            catatan
        )
        VALUES
        (
            :pohon_id,
            :user_id,
            :tanggal_monitoring,
            :kesehatan_monitoring,
            :catatan
        )
    ");

    $stmt->execute([
        ':pohon_id' => $_POST['pohon_id'],
        ':user_id' => $user_id,
        ':tanggal_monitoring' => $_POST['tanggal_monitoring'] ?? date('Y-m-d'),
        ':kesehatan_monitoring' => $_POST['kesehatan_monitoring'] ?? null,
        ':catatan' => $_POST['catatan'] ?? null
    ]);

    $monitoring_id = $pdo->lastInsertId();

    // Upload media

// ==========================
// Upload Media Monitoring
// ==========================

$todayFolder = date('Y-m-d');

$fotoDir = "../../media/monitoring/$todayFolder/foto/";
$videoDir = "../../media/monitoring/$todayFolder/video/";

if (!file_exists($fotoDir)) {
    mkdir($fotoDir, 0777, true);
}

if (!file_exists($videoDir)) {
    mkdir($videoDir, 0777, true);
}

if (isset($_FILES['files'])) {

    // Normalisasi jika upload hanya 1 file
    if (!is_array($_FILES['files']['tmp_name'])) {

        $_FILES['files']['tmp_name'] = [
            $_FILES['files']['tmp_name']
        ];

        $_FILES['files']['name'] = [
            $_FILES['files']['name']
        ];

        $_FILES['files']['type'] = [
            $_FILES['files']['type']
        ];

        $_FILES['files']['error'] = [
            $_FILES['files']['error']
        ];
    }

    $fotoCounter = 1;
    $videoCounter = 1;

    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {

        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        $originalName = $_FILES['files']['name'][$key];

        $mimeType = $_FILES['files']['type'][$key];

        $extension = strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );

        // Validasi ekstensi
        $allowedImages = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        $allowedVideos = [
            'mp4',
            'mov',
            'avi',
            'mkv'
        ];

        $allowedExtensions = array_merge(
            $allowedImages,
            $allowedVideos
        );

        if (!in_array($extension, $allowedExtensions)) {
            continue;
        }

        // ==========================
        // VIDEO
        // ==========================
        if (str_contains($mimeType, 'video')) {

            $mediaType = 'video';

            $newName =
                date('Ymd_His') .
                '_' .
                $videoCounter .
                '.' .
                $extension;

            $savePath =
                $videoDir . $newName;

            $dbPath =
                "media/monitoring/$todayFolder/video/$newName";

            $videoCounter++;

        }
        // ==========================
        // FOTO
        // ==========================
        else {

            $mediaType = 'foto';

            $newName =
                date('Ymd_His') .
                '_' .
                $fotoCounter .
                '.' .
                $extension;

            $savePath =
                $fotoDir . $newName;

            $dbPath =
                "media/monitoring/$todayFolder/foto/$newName";

            $fotoCounter++;
        }

        if (
            move_uploaded_file(
                $tmpName,
                $savePath
            )
        ) {

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
                ':monitoring_id' => $monitoring_id,
                ':media_type' => $mediaType,
                ':file_name' => $originalName,
                ':file_path' => $dbPath
            ]);
        }
    }
}

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'monitoring_id' => $monitoring_id,
        'message' => 'Monitoring berhasil disimpan'
    ]);

} catch (Exception $e) {

    $pdo->rollBack();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}