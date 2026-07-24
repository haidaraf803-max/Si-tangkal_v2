<?php

header('Content-Type: application/json');

// API ini HARUS selalu keluar JSON valid. config.php (lewat require di
// bawah) menampilkan error sebagai HTML untuk memudahkan debug halaman
// web biasa — itu bagus untuk halaman, tapi merusak response JSON kalau
// ada warning/notice yang tercetak. Matikan tampilan HTML khusus di sini,
// dan tangkap fatal error supaya tetap keluar sebagai JSON, bukan HTML.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/monitoring/create] ' . $errstr);
    return true; // cegah PHP mencetak warning/notice sebagai HTML ke body
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

// Session sudah otomatis dimulai oleh config.php (lihat require di atas) —
// TIDAK perlu panggil session_start() lagi di sini (memanggilnya dua kali
// dalam satu request memicu warning "session already started").

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Anda belum login'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// =====================================================================
// VALIDASI JARAK GPS (opsional, backward-compatible)
// -----------------------------------------------------------------
// Jika klien mengirim koordinat perangkat (lat, lng), monitoring hanya
// boleh disimpan bila jaraknya cukup dekat dengan koordinat pohon
// (default maksimum 1 km). Kalau klien TIDAK mengirim lat/lng (mis.
// aplikasi lama / input manual dari admin), validasi ini dilewati saja
// supaya tidak merusak konsumen API yang sudah ada.
//
// "accuracy" (meter, dari navigator.geolocation) dipakai sebagai
// toleransi karena GPS di dalam ruangan / perangkat tanpa GPS chip
// bisa meleset ratusan meter. Jarak efektif = jarak - accuracy.
// =====================================================================
define('MONITORING_MAX_DISTANCE_METERS', 1000);

function haversineDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371000; // meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

try {

    $pdo->beginTransaction();

    // Validasi sederhana
    if (empty($_POST['pohon_id'])) {
        throw new Exception('Pohon wajib dipilih');
    }

    // Validasi jarak GPS (jika koordinat perangkat dikirim)
    $userLat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float) $_POST['lat'] : null;
    $userLng = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float) $_POST['lng'] : null;
    $accuracy = isset($_POST['accuracy']) && $_POST['accuracy'] !== '' ? (float) $_POST['accuracy'] : 0;

    /*
     * ========================================================
     * SEMENTARA DI-NONAKTIFKAN untuk keperluan testing —
     * supaya bisa dipastikan dulu proses input datanya berhasil
     * atau tidak, terlepas dari validasi jarak.
     * Hapus tanda komentar ini untuk mengaktifkan lagi validasi
     * jarak 1km-nya.
     * ========================================================
    if ($userLat !== null && $userLng !== null) {
        $stmtPohon = $pdo->prepare("SELECT koordinat_x, koordinat_y FROM pohon WHERE id = ?");
        $stmtPohon->execute([$_POST['pohon_id']]);
        $pohonCoord = $stmtPohon->fetch(PDO::FETCH_ASSOC);

        if ($pohonCoord && $pohonCoord['koordinat_x'] !== null && $pohonCoord['koordinat_y'] !== null) {
            $distance = haversineDistanceMeters(
                $userLat,
                $userLng,
                (float) $pohonCoord['koordinat_y'],
                (float) $pohonCoord['koordinat_x']
            );

            // Beri toleransi sebesar akurasi GPS device (maksimal 500m toleransi)
            $tolerance = min($accuracy, 500);
            $effectiveDistance = max(0, $distance - $tolerance);

            if ($effectiveDistance > MONITORING_MAX_DISTANCE_METERS) {
                throw new Exception(
                    'Lokasi Anda terlalu jauh dari pohon ini (±' . round($distance) . ' meter). '
                    . 'Monitoring hanya bisa dilakukan dalam radius ' . MONITORING_MAX_DISTANCE_METERS . ' meter dari lokasi pohon.'
                );
            }
        }
    }
    */

    // Simpan monitoring
    $stmt = $pdo->prepare("
        INSERT INTO monitoring
        (
            pohon_id,
            user_id,
            tanggal_monitoring,
            kesehatan_monitoring,
            catatan,
            tinggi_pohon,
            diameter_batang,
            lebar_tajuk,
            jenis_gangguan,
            tingkat_keparahan,
            rekomendasi_tindakan,
            status_tindak_lanjut,
            latitude,
            longitude
        )
        VALUES
        (
            :pohon_id,
            :user_id,
            :tanggal_monitoring,
            :kesehatan_monitoring,
            :catatan,
            :tinggi_pohon,
            :diameter_batang,
            :lebar_tajuk,
            :jenis_gangguan,
            :tingkat_keparahan,
            :rekomendasi_tindakan,
            :status_tindak_lanjut,
            :latitude,
            :longitude
        )
    ");

    $stmt->execute([
        ':pohon_id' => $_POST['pohon_id'],
        ':user_id' => $user_id,
        ':tanggal_monitoring' => $_POST['tanggal_monitoring'] ?? date('Y-m-d'),
        ':kesehatan_monitoring' => $_POST['kesehatan_monitoring'] ?? null,
        ':catatan' => $_POST['catatan'] ?? null,
        ':tinggi_pohon' => ($_POST['tinggi_pohon'] ?? '') !== '' ? (float) $_POST['tinggi_pohon'] : null,
        ':diameter_batang' => ($_POST['diameter_batang'] ?? '') !== '' ? (float) $_POST['diameter_batang'] : null,
        ':lebar_tajuk' => ($_POST['lebar_tajuk'] ?? '') !== '' ? (float) $_POST['lebar_tajuk'] : null,
        ':jenis_gangguan' => $_POST['jenis_gangguan'] ?? null,
        ':tingkat_keparahan' => $_POST['tingkat_keparahan'] ?? null,
        ':rekomendasi_tindakan' => $_POST['rekomendasi_tindakan'] ?? null,
        ':status_tindak_lanjut' => $_POST['status_tindak_lanjut'] ?? 'Belum',
        // pakai koordinat GPS device yang sama dengan yang dipakai untuk validasi jarak di atas
        ':latitude' => $userLat,
        ':longitude' => $userLng
    ]);

    $monitoring_id = $pdo->lastInsertId();

    // =====================================================================
    // SINKRONKAN STATUS KESEHATAN POHON
    // -----------------------------------------------------------------
    // Tabel `pohon` menyimpan kondisi TERKINI pohon, sedangkan `monitoring`
    // menyimpan RIWAYAT tiap pemeriksaan. Supaya client cukup 1x hit untuk
    // "nambah monitoring" DAN "update status pohon", kita sinkronkan di
    // sini, masih di dalam transaksi yang sama dengan insert di atas —
    // kalau salah satu gagal, keduanya di-rollback bareng.
    // =====================================================================
    if (!empty($_POST['kesehatan_monitoring'])) {
        $stmtSyncPohon = $pdo->prepare(
            "UPDATE pohon SET kesehatan = ? WHERE id = ?"
        );
        $stmtSyncPohon->execute([
            $_POST['kesehatan_monitoring'],
            $_POST['pohon_id']
        ]);
    }

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