<?php

header('Content-Type: application/json');

// Lihat catatan di api/monitoring/create.php — API ini harus selalu
// keluar JSON valid, jadi error/warning tidak ditampilkan sebagai HTML.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/pohon/create] ' . $errstr);
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

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Anda belum login',
    ]);
    exit;
}

// ==========================================
// Ekstensi foto yang diizinkan (1 foto per pohon, kolom `foto`)
// ==========================================
$allowedFotoExt = ['jpg', 'jpeg', 'png', 'webp'];

/**
 * Upload 1 file foto dari $_FILES['foto']. Mengembalikan nama file
 * tersimpan, atau null kalau tidak ada file / gagal / ekstensi ditolak.
 */
function uploadFotoPohon(?array $file, array $allowedExt): ?string
{
    if (empty($file) || empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExt, true)) {
        return null;
    }

    $dir = "../../assets/foto/";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $newName  = 'pohon_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
    $savePath = $dir . $newName;

    return move_uploaded_file($file['tmp_name'], $savePath) ? $newName : null;
}

try {

    // =========================
    // VALIDASI DASAR
    // =========================
    if (empty($_POST['nama_lokal'])) {
        throw new Exception('Nama lokal pohon wajib diisi');
    }

    $pdo->beginTransaction();

    $foto = uploadFotoPohon($_FILES['foto'] ?? null, $allowedFotoExt);

    $stmt = $pdo->prepare("
        INSERT INTO pohon
        (
            no_pohon, nama_lokal, nama_latin, family, tahun_tanam, habitus,
            status_kel, volume, kelas_awet, kelas_kuat, berat_jenis, kesehatan,
            serapan_co, produksi_o, nama_jalan, kelurahan, kecamatan,
            koordinat_x, koordinat_y, keterangan, foto, flag
        )
        VALUES
        (
            :no_pohon, :nama_lokal, :nama_latin, :family, :tahun_tanam, :habitus,
            :status_kel, :volume, :kelas_awet, :kelas_kuat, :berat_jenis, :kesehatan,
            :serapan_co, :produksi_o, :nama_jalan, :kelurahan, :kecamatan,
            :koordinat_x, :koordinat_y, :keterangan, :foto, :flag
        )
    ");

    $stmt->execute([
        ':no_pohon'    => ($_POST['no_pohon'] ?? '') !== '' ? (int) $_POST['no_pohon'] : null,
        ':nama_lokal'  => trim($_POST['nama_lokal']),
        ':nama_latin'  => $_POST['nama_latin'] ?? null,
        ':family'      => $_POST['family'] ?? null,
        ':tahun_tanam' => $_POST['tahun_tanam'] ?? null,
        ':habitus'     => $_POST['habitus'] ?? null,
        ':status_kel'  => $_POST['status_kel'] ?? null,
        ':volume'      => ($_POST['volume'] ?? '') !== '' ? (float) $_POST['volume'] : 0,
        ':kelas_awet'  => $_POST['kelas_awet'] ?? null,
        ':kelas_kuat'  => $_POST['kelas_kuat'] ?? null,
        ':berat_jenis' => ($_POST['berat_jenis'] ?? '') !== '' ? (float) $_POST['berat_jenis'] : 0,
        ':kesehatan'   => $_POST['kesehatan'] ?? 'Sehat',
        ':serapan_co'  => ($_POST['serapan_co'] ?? '') !== '' ? (float) $_POST['serapan_co'] : 0,
        ':produksi_o'  => ($_POST['produksi_o'] ?? '') !== '' ? (float) $_POST['produksi_o'] : null,
        ':nama_jalan'  => $_POST['nama_jalan'] ?? null,
        ':kelurahan'   => $_POST['kelurahan'] ?? null,
        ':kecamatan'   => $_POST['kecamatan'] ?? null,
        ':koordinat_x' => ($_POST['koordinat_x'] ?? '') !== '' ? (float) $_POST['koordinat_x'] : 0,
        ':koordinat_y' => ($_POST['koordinat_y'] ?? '') !== '' ? (float) $_POST['koordinat_y'] : null,
        ':keterangan'  => $_POST['keterangan'] ?? null,
        ':foto'        => $foto,
        ':flag'        => $_POST['flag'] ?? '0',
    ]);

    $pohon_id = (int) $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'pohon_id' => $pohon_id,
        'message' => 'Data pohon berhasil ditambahkan',
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
