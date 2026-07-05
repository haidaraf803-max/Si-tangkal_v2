<?php

header('Content-Type: application/json');

session_start();

require_once '../config/database.php';

try {

    // ==========================================
    // CEK LOGIN
    // ==========================================
    if (!isset($_SESSION['user_id'])) {

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Anda belum login'
        ]);

        exit;
    }

    // ==========================================
    // DETAIL MONITORING BERDASARKAN ID
    // ==========================================
    if (isset($_GET['id']) && !empty($_GET['id'])) {

        $id = (int)$_GET['id'];

        $stmt = $pdo->prepare("
            SELECT *
            FROM monitoring
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $monitoring = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$monitoring) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Data monitoring tidak ditemukan'
            ]);

            exit;
        }

        // ==========================
        // AMBIL MEDIA
        // ==========================
        $stmtMedia = $pdo->prepare("
            SELECT *
            FROM monitoring_media
            WHERE monitoring_id = ?
            ORDER BY created_at ASC
        ");

        $stmtMedia->execute([$monitoring['id']]);

        $media = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

        $monitoring['media_count'] = count($media);
        $monitoring['media'] = $media;

        echo json_encode([
            'success' => true,
            'data' => $monitoring
        ]);

        exit;
    }

    // ==========================================
    // RIWAYAT MONITORING BERDASARKAN POHON_ID
    // ==========================================
    if (isset($_GET['pohon_id']) && !empty($_GET['pohon_id'])) {

        $pohonId = (int)$_GET['pohon_id'];

        $stmt = $pdo->prepare("
            SELECT *
            FROM monitoring
            WHERE pohon_id = ?
            ORDER BY tanggal_monitoring DESC
        ");

        $stmt->execute([$pohonId]);

        $monitorings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($monitorings as &$monitoring) {

            $stmtMedia = $pdo->prepare("
                SELECT *
                FROM monitoring_media
                WHERE monitoring_id = ?
                ORDER BY created_at ASC
            ");

            $stmtMedia->execute([$monitoring['id']]);

            $media = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

            $monitoring['media_count'] = count($media);
            $monitoring['media'] = $media;
        }

        echo json_encode([
            'success' => true,
            'total' => count($monitorings),
            'data' => $monitorings
        ]);

        exit;
    }

    // ==========================================
    // SEMUA DATA MONITORING
    // ==========================================
    $stmt = $pdo->query("
        SELECT *
        FROM monitoring
        ORDER BY created_at DESC
    ");

    $monitorings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($monitorings as &$monitoring) {

        $stmtMedia = $pdo->prepare("
            SELECT *
            FROM monitoring_media
            WHERE monitoring_id = ?
        ");

        $stmtMedia->execute([$monitoring['id']]);

        $media = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

        $monitoring['media_count'] = count($media);
        $monitoring['media'] = $media;
    }

    echo json_encode([
        'success' => true,
        'total' => count($monitorings),
        'data' => $monitorings
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database Error',
        'error' => $e->getMessage()
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}