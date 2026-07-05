<?php

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();

// ==========================================================
// Parameter dari client
// q            -> pencarian bebas (nama_lokal, kesehatan, family)
// kesehatan[]  -> filter kesehatan pohon (Sehat / Kurang Sehat)
// status_kel[] -> filter status konservasi (status_kel)
// ==========================================================
$q         = trim($_GET['q'] ?? '');
$kesehatan = $_GET['kesehatan'] ?? [];
$statusKel = $_GET['status_kel'] ?? [];

if (!is_array($kesehatan)) $kesehatan = [$kesehatan];
if (!is_array($statusKel)) $statusKel = [$statusKel];

$kesehatan = array_values(array_filter(array_map('trim', $kesehatan), fn($v) => $v !== ''));
$statusKel = array_values(array_filter(array_map('trim', $statusKel), fn($v) => $v !== ''));

try {

    $where  = [];
    $params = [];

    // ------------------------------------------------------
    // Pencarian bebas — hanya di nama_lokal, kesehatan, family
    // sesuai kolom yang tersedia di database `pohon`
    // ------------------------------------------------------
    if ($q !== '') {
        // nama_lokal & family: pencarian sebagian (LIKE %kata%).
        // kesehatan: dicocokkan sebagai AWALAN (LIKE 'kata%') — bukan exact,
        // bukan pula substring biasa. Ini supaya:
        //   - ketik "sehat"  -> HANYA cocok ke "Sehat" (karena "Kurang Sehat"
        //     tidak diawali kata "sehat")
        //   - ketik "kurang" -> cocok ke "Kurang Sehat"
        $where[] = '(nama_lokal LIKE ? OR family LIKE ? OR kesehatan LIKE ?)';
        $keyword = "%{$q}%";
        $kesehatanKeyword = "{$q}%";
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $kesehatanKeyword;
    }

    // ------------------------------------------------------
    // Filter kesehatan (checkbox, boleh lebih dari satu)
    // ------------------------------------------------------
    if (!empty($kesehatan)) {
        $placeholders = implode(',', array_fill(0, count($kesehatan), '?'));
        $where[] = "kesehatan IN ($placeholders)";
        foreach ($kesehatan as $k) $params[] = $k;
    }

    // ------------------------------------------------------
    // Filter status_kel (checkbox, boleh lebih dari satu)
    // ------------------------------------------------------
    if (!empty($statusKel)) {
        $placeholders = implode(',', array_fill(0, count($statusKel), '?'));
        $where[] = "status_kel IN ($placeholders)";
        foreach ($statusKel as $s) $params[] = $s;
    }

    $sql = "SELECT * FROM pohon";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY nama_lokal ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $trees = [];

    foreach ($rows as $row) {

        $trees[] = [

            'id' => (int)$row['id'],

            'no_pohon' => $row['no_pohon'] ?? null,

            'name' => $row['nama_lokal'] ?? '-',

            'scientific_name' => $row['nama_latin'] ?? '-',

            'family' => $row['family'] ?? '-',

            'lat' => isset($row['koordinat_y']) ? (float)$row['koordinat_y'] : null,

            'lng' => isset($row['koordinat_x']) ? (float)$row['koordinat_x'] : null,

            'address' => $row['nama_jalan'] ?? '',

            'village' => $row['kelurahan'] ?? '',

            'district' => $row['kecamatan'] ?? '',

            'kesehatan' => $row['kesehatan'] ?? '',

            'status_kel' => $row['status_kel'] ?? '',

            'image_url' => !empty($row['foto']) ? ('assets/images/trees/' . $row['foto']) : '',

            'tahun_tanam' => $row['tahun_tanam'] ?? '',

            'habitus' => $row['habitus'] ?? '',

            'volume' => $row['volume'] ?? '',

            'description' => $row['keterangan'] ?? '',

            'flag' => $row['flag'] ?? ''

        ];

    }

    echo json_encode($trees, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}
