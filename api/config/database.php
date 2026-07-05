<?php
/**
 * api/config/database.php
 * ---------------------------------------------------------
 * SHIM KOMPATIBILITAS — koneksi DB API diarahkan ke /config.php
 * (root) supaya API memakai kredensial & koneksi yang SAMA
 * dengan seluruh aplikasi (web publik + Admin).
 * Menyediakan variabel $pdo untuk kode API lama.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../../config.php';

// $pdo sudah otomatis tersedia dari config.php, baris ini hanya
// memastikan konsisten walau config.php di masa depan berubah nama variabel.
$pdo = getPDO();
