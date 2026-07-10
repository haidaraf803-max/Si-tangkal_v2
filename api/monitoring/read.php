<?php

header('Content-Type: application/json');

// Lihat catatan di api/monitoring/create.php — API ini harus selalu
// keluar JSON valid, jadi error/warning tidak ditampilkan sebagai HTML.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/monitoring/read] ' . $errstr);
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

// ==========================================
// HELPER: susun kolom hasil JOIN pohon_* menjadi
// objek 'pohon' yang berisi detail & koordinat pohon,
// lalu buang kolom pohon_* mentahnya dari level atas
// ==========================================
// ==========================================
// HELPER: pastikan kolom numerik baru pada tabel monitoring
// keluar sebagai number di JSON, bukan string (PDO mengembalikan
// DECIMAL/FLOAT sebagai string secara default)
// ==========================================
function castMonitoringDetail(array $monitoring): array
{
    foreach (['tinggi_pohon', 'diameter_batang', 'lebar_tajuk', 'latitude', 'longitude'] as $field) {
        if (isset($monitoring[$field]) && $monitoring[$field] !== null && $monitoring[$field] !== '') {
            $monitoring[$field] = (float) $monitoring[$field];
        }
    }
    return $monitoring;
}

function attachPohonDetail(array $monitoring): array
{
    $monitoring = castMonitoringDetail($monitoring);

    $monitoring['pohon'] = [
        'no_pohon'   => $monitoring['pohon_no_pohon']   ?? null,
        'nama_lokal' => $monitoring['pohon_nama_lokal'] ?? null,
        'nama_latin' => $monitoring['pohon_nama_latin'] ?? null,
        'family'     => $monitoring['pohon_family']     ?? null,
        'lat'        => isset($monitoring['pohon_koordinat_y']) ? (float)$monitoring['pohon_koordinat_y'] : null,
        'lng'        => isset($monitoring['pohon_koordinat_x']) ? (float)$monitoring['pohon_koordinat_x'] : null,
        'nama_jalan' => $monitoring['pohon_nama_jalan'] ?? null,
        'kelurahan'  => $monitoring['pohon_kelurahan']  ?? null,
        'kecamatan'  => $monitoring['pohon_kecamatan']  ?? null,
        'kesehatan'  => $monitoring['pohon_kesehatan']  ?? null,
        'status_kel' => $monitoring['pohon_status_kel'] ?? null,
        'image_url'  => !empty($monitoring['pohon_foto']) ? ('assets/foto/' . $monitoring['pohon_foto']) : '',
    ];

    // buang kolom pohon_* mentah supaya response tetap rapi
    foreach ($monitoring as $key => $value) {
        if (strpos($key, 'pohon_') === 0) {
            unset($monitoring[$key]);
        }
    }

    return $monitoring;
}

try {

    // ==========================================
    // CEK LOGIN
    // ==========================================
    // if (!isset($_SESSION['user_id'])) {

    //     http_response_code(401);

    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Anda belum login'
    //     ]);

    //     exit;
    // }

    // ==========================================
    // DETAIL MONITORING BERDASARKAN ID
    // ==========================================
    if (isset($_GET['id']) && !empty($_GET['id'])) {

        $id = (int)$_GET['id'];

        $stmt = $pdo->prepare("
            SELECT
                monitoring.*,
                pohon.no_pohon      AS pohon_no_pohon,
                pohon.nama_lokal    AS pohon_nama_lokal,
                pohon.nama_latin    AS pohon_nama_latin,
                pohon.family        AS pohon_family,
                pohon.koordinat_x   AS pohon_koordinat_x,
                pohon.koordinat_y   AS pohon_koordinat_y,
                pohon.nama_jalan    AS pohon_nama_jalan,
                pohon.kelurahan     AS pohon_kelurahan,
                pohon.kecamatan     AS pohon_kecamatan,
                pohon.kesehatan     AS pohon_kesehatan,
                pohon.status_kel    AS pohon_status_kel,
                pohon.foto          AS pohon_foto
            FROM monitoring
            LEFT JOIN pohon ON pohon.id = monitoring.pohon_id
            WHERE monitoring.id = ?
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

        $monitoring = attachPohonDetail($monitoring);

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
            SELECT
                monitoring.*,
                pohon.no_pohon      AS pohon_no_pohon,
                pohon.nama_lokal    AS pohon_nama_lokal,
                pohon.nama_latin    AS pohon_nama_latin,
                pohon.family        AS pohon_family,
                pohon.koordinat_x   AS pohon_koordinat_x,
                pohon.koordinat_y   AS pohon_koordinat_y,
                pohon.nama_jalan    AS pohon_nama_jalan,
                pohon.kelurahan     AS pohon_kelurahan,
                pohon.kecamatan     AS pohon_kecamatan,
                pohon.kesehatan     AS pohon_kesehatan,
                pohon.status_kel    AS pohon_status_kel,
                pohon.foto          AS pohon_foto
            FROM monitoring
            LEFT JOIN pohon ON pohon.id = monitoring.pohon_id
            WHERE monitoring.pohon_id = ?
            ORDER BY monitoring.tanggal_monitoring DESC
        ");

        $stmt->execute([$pohonId]);

        $monitorings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($monitorings as &$monitoring) {

            $monitoring = attachPohonDetail($monitoring);

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
        unset($monitoring);

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
        SELECT
            monitoring.*,
            pohon.no_pohon      AS pohon_no_pohon,
            pohon.nama_lokal    AS pohon_nama_lokal,
            pohon.nama_latin    AS pohon_nama_latin,
            pohon.family        AS pohon_family,
            pohon.koordinat_x   AS pohon_koordinat_x,
            pohon.koordinat_y   AS pohon_koordinat_y,
            pohon.nama_jalan    AS pohon_nama_jalan,
            pohon.kelurahan     AS pohon_kelurahan,
            pohon.kecamatan     AS pohon_kecamatan,
            pohon.kesehatan     AS pohon_kesehatan,
            pohon.status_kel    AS pohon_status_kel,
            pohon.foto          AS pohon_foto
        FROM monitoring
        LEFT JOIN pohon ON pohon.id = monitoring.pohon_id
        ORDER BY monitoring.created_at DESC
    ");

    $monitorings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($monitorings as &$monitoring) {

        $monitoring = attachPohonDetail($monitoring);

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
    unset($monitoring);

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