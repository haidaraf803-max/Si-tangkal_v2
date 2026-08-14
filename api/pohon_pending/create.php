<?php
/**
 * api/pohon_pending/create.php
 * ---------------------------------------------------------
 * Kirim pengajuan pohon baru. Data DISIMPAN KE STAGING
 * (`pohon_pending`, status='pending') — TIDAK langsung masuk ke
 * tabel `pohon`. Gunakan api/pohon_pending/validate.php untuk
 * memvalidasi & memindahkannya ke tabel pohon.
 *
 * Method: POST (multipart/form-data, sama seperti api/pohon/create.php,
 * agar upload foto ikut terkirim)
 * ---------------------------------------------------------
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/pohon_pending/create] ' . $errstr);
    return true;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.']);
    }
});

require_once '../config/rbac_guard.php';
require_once __DIR__ . '/../../Admin/core/PohonPendingModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan, gunakan POST']);
    exit;
}

$allowedFotoExt = ['jpg', 'jpeg', 'png', 'webp'];

function uploadFotoPohonPending(?array $file, array $allowedExt): ?string
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
    apiRequirePermission($pdo, 'pohon', 'create');

    if (empty($_POST['nama_lokal'])) {
        throw new Exception('Nama lokal pohon wajib diisi');
    }
    if (empty($_POST['kesehatan'])) {
        throw new Exception('Kondisi kesehatan wajib diisi');
    }
    if (empty($_POST['nama_jalan'])) {
        throw new Exception('Nama jalan wajib diisi');
    }
    if (!isset($_POST['koordinat_x']) || !isset($_POST['koordinat_y']) ||
        $_POST['koordinat_x'] === '' || $_POST['koordinat_y'] === '') {
        throw new Exception('Latitude dan longitude wajib diisi');
    }

    $foto = uploadFotoPohonPending($_FILES['foto'] ?? null, $allowedFotoExt);

    $model = new PohonPendingModel($pdo);

    $id = $model->create([
        'no_pohon'    => $_POST['no_pohon'] ?? null,
        'nama_lokal'  => $_POST['nama_lokal'],
        'nama_latin'  => $_POST['nama_latin'] ?? '',
        'family'      => $_POST['family'] ?? '',
        'tahun_tanam' => $_POST['tahun_tanam'] ?? '',
        'habitus'     => $_POST['habitus'] ?? '',
        'status_kel'  => $_POST['status_kel'] ?? '',
        'volume'      => $_POST['volume'] ?? 0,
        'kelas_awet'  => $_POST['kelas_awet'] ?? '',
        'kelas_kuat'  => $_POST['kelas_kuat'] ?? '',
        'berat_jenis' => $_POST['berat_jenis'] ?? 0,
        'kesehatan'   => $_POST['kesehatan'],
        'serapan_co'  => $_POST['serapan_co'] ?? 0,
        'produksi_o'  => $_POST['produksi_o'] ?? null,
        'nama_jalan'  => $_POST['nama_jalan'],
        'kelurahan'   => $_POST['kelurahan'] ?? '',
        'kecamatan'   => $_POST['kecamatan'] ?? '',
        'koordinat_x' => $_POST['koordinat_x'],
        'koordinat_y' => $_POST['koordinat_y'],
        'keterangan'  => $_POST['keterangan'] ?? '',
        'umur_pohon'  => $_POST['umur_pohon'] ?? '',
        'foto'        => $foto,
    ], $user_id);

    if (!$id) {
        throw new Exception('Gagal menyimpan pengajuan data pohon');
    }

    echo json_encode([
        'success'    => true,
        'pending_id' => $id,
        'message'    => 'Pengajuan pohon berhasil dikirim & menunggu validasi.',
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
