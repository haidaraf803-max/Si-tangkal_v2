<?php

/**
 * api/layer_luas.php
 * ---------------------------------------------------------
 * Endpoint publik: total luas per layer, dipakai di peta (maps.php /
 * Admin/map.php) supaya saat layer "Ruang Terbuka Hijau" atau "Tajuk
 * Pohon" dinyalakan, pengguna bisa langsung tahu luas totalnya
 * (skema terbaru 20 Agustus 2026, poin Peta #4).
 *
 * Sumber data: tabel deliniasi_rth & deliniasi_tajuk (poligon yang
 * sudah didigitasi manual lewat menu Admin > Peta > Deliniasi), BUKAN
 * dari layer citra WMS GeoServer — layer WMS (Tutupan Lahan IKTL,
 * Lahan Kritis, Tajuk Pohon versi citra) tidak punya geometri vektor
 * di sisi aplikasi ini sehingga luasnya tidak bisa dihitung di sini.
 * Untuk layer tersebut, endpoint ini mengembalikan `available: false`.
 * ---------------------------------------------------------
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../Admin/core/DeliniasiModel.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();

try {
    $rthModel   = new DeliniasiRthModel($pdo);
    $tajukModel = new DeliniasiTajukModel($pdo);

    $rthM2   = $rthModel->totalLuasM2();
    $tajukM2 = $tajukModel->totalLuasM2();

    echo json_encode([
        'success' => true,
        'layers'  => [
            'green' => [
                'label'     => 'Ruang Terbuka Hijau',
                'available' => true,
                'luas_m2'   => round($rthM2, 2),
                'luas_ha'   => round($rthM2 / 10000, 2),
                'persen_kota' => $rthModel->persentaseRth(),
            ],
            'pucuk' => [
                'label'     => 'Tajuk Pohon (hasil deliniasi)',
                'available' => true,
                'luas_m2'   => round($tajukM2, 2),
                'luas_ha'   => round($tajukM2 / 10000, 2),
            ],
            'tutupan-lahan' => [
                'label'     => 'Tutupan Lahan IKTL',
                'available' => false,
                'alasan'    => 'Layer citra WMS, belum ada data vektor untuk dihitung luasnya.',
            ],
            'lahan-kritis' => [
                'label'     => 'Lahan Kritis',
                'available' => false,
                'alasan'    => 'Layer citra WMS, belum ada data vektor untuk dihitung luasnya.',
            ],
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
