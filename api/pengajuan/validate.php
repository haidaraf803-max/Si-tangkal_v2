<?php

/**
 * DIHAPUS DARI ALUR — endpoint ini sengaja dinonaktifkan.
 * Langkah "Divalidasi" oleh Validator telah dihapus dari alur pengajuan
 * pemangkasan pohon. Alur sekarang: diajukan -> disurvey -> (Tim Tangkas
 * unggah foto sesudah) -> selesai. Lihat Admin/core/PengajuanModel.php.
 */

header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'success' => false,
    'message' => 'Langkah validasi sudah dihapus dari alur pengajuan. Gunakan endpoint survey.php dan eksekusi.php.',
]);
