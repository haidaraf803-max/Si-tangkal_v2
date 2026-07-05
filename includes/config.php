<?php
/**
 * includes/config.php
 * ---------------------------------------------------------
 * SHIM KOMPATIBILITAS — bukan config asli.
 * Semua konfigurasi sesungguhnya ada di /config.php (root),
 * satu-satunya sumber koneksi DB & konstanta aplikasi.
 * File ini hanya diarahkan ke sana supaya kode lama yang
 * masih memuat 'includes/config.php' tetap berjalan dan
 * tetap memakai config yang SAMA.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config.php';
