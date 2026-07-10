<?php

header('Content-Type: application/json');

// API ini harus selalu keluar JSON valid — lihat catatan yang sama di
// api/monitoring/create.php. Error/warning tidak ditampilkan sebagai HTML.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    error_log('[api/pohon/read] ' . $errstr);
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

// ==========================================
// HELPER: pastikan kolom numerik keluar sebagai number di JSON,
// bukan string (PDO mengembalikan DECIMAL/FLOAT sebagai string
// secara default), dan tambahkan field turunan image_url.
// ==========================================
function castPohon(array $pohon): array
{
    foreach (['volume', 'berat_jenis', 'serapan_co', 'produksi_o', 'koordinat_x', 'koordinat_y'] as $field) {
        if (isset($pohon[$field]) && $pohon[$field] !== null && $pohon[$field] !== '') {
            $pohon[$field] = (float) $pohon[$field];
        }
    }

    if (isset($pohon['id'])) {
        $pohon['id'] = (int) $pohon['id'];
    }

    if (isset($pohon['no_pohon']) && $pohon['no_pohon'] !== null) {
        $pohon['no_pohon'] = (int) $pohon['no_pohon'];
    }

    $pohon['image_url'] = !empty($pohon['foto']) ? ('assets/foto/' . $pohon['foto']) : '';

    return $pohon;
}

try {

    // ==========================================
    // DETAIL POHON BERDASARKAN ID
    // ==========================================
    if (isset($_GET['id']) && $_GET['id'] !== '') {

        $id = (int) $_GET['id'];

        $stmt = $pdo->prepare("SELECT * FROM pohon WHERE id = ?");
        $stmt->execute([$id]);

        $pohon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pohon) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Data pohon tidak ditemukan',
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => castPohon($pohon),
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // ==========================================
    // DAFTAR POHON — pencarian bebas & filter
    // q            -> pencarian bebas (nama_lokal, nama_latin, family, nama_jalan, kesehatan)
    // kesehatan[]  -> filter kesehatan pohon (bisa lebih dari satu)
    // status_kel[] -> filter status konservasi (bisa lebih dari satu)
    // ==========================================
    $q         = trim($_GET['q'] ?? '');
    $kesehatan = $_GET['kesehatan'] ?? [];
    $statusKel = $_GET['status_kel'] ?? [];

    if (!is_array($kesehatan)) $kesehatan = [$kesehatan];
    if (!is_array($statusKel)) $statusKel = [$statusKel];

    $kesehatan = array_values(array_filter(array_map('trim', $kesehatan), fn($v) => $v !== ''));
    $statusKel = array_values(array_filter(array_map('trim', $statusKel), fn($v) => $v !== ''));

    $where  = [];
    $params = [];

    if ($q !== '') {
        // nama_lokal, nama_latin, nama_jalan, family: pencarian substring (LIKE %kata%).
        // kesehatan: dicocokkan sebagai AWALAN (LIKE 'kata%') supaya ketik "sehat"
        // tidak ikut mencocokkan "Kurang Sehat".
        $where[] = '(nama_lokal LIKE ? OR nama_latin LIKE ? OR family LIKE ? OR nama_jalan LIKE ? OR kesehatan LIKE ?)';
        $keyword = "%{$q}%";
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = "{$q}%";
    }

    if (!empty($kesehatan)) {
        $placeholders = implode(',', array_fill(0, count($kesehatan), '?'));
        $where[] = "kesehatan IN ($placeholders)";
        foreach ($kesehatan as $k) $params[] = $k;
    }

    if (!empty($statusKel)) {
        $placeholders = implode(',', array_fill(0, count($statusKel), '?'));
        $where[] = "status_kel IN ($placeholders)";
        foreach ($statusKel as $s) $params[] = $s;
    }

    $sql = "SELECT * FROM pohon";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = array_map('castPohon', $rows);

    echo json_encode([
        'success' => true,
        'total' => count($data),
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database Error',
        'error' => $e->getMessage(),
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
