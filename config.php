<?php
/**
 * ============================================================
 *  Si-TANGKAL — KONFIGURASI UTAMA (SATU-SATUNYA CONFIG)
 *  Sistem Informasi Pohon Kota Cimahi
 * ------------------------------------------------------------
 *  File ini adalah SATU-SATUNYA sumber konfigurasi aplikasi.
 *  Semua file lain (Admin/, api/, includes/, pages/, dll) WAJIB
 *  memuat file ini (langsung atau melalui shim) agar seluruh
 *  bagian aplikasi memakai koneksi database, session, dan
 *  konstanta yang SAMA & SELARAS.
 *
 *  Jangan buat config.php baru di folder lain. Jika sebuah
 *  modul butuh koneksi DB / session, cukup:
 *      require_once __DIR__ . '/config.php';   // sesuaikan path relatif
 * ============================================================
 */

// =======================================================
// SESSION (satu session untuk seluruh aplikasi)
// =======================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
// ERROR REPORTING & TIMEZONE
// =======================================================
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', '1');

// =======================================================
// INFORMASI APLIKASI
// =======================================================
define('APP_NAME', 'Si-TANGKAL');
define('APP_FULL_NAME', 'Sistem Informasi Pohon Kota Cimahi');

define('BASE_PATH', __DIR__);
define('DATA_PATH', BASE_PATH . '/data');
define('GEOJSON_PATH', DATA_PATH . '/geojson');

// Base URL relatif aplikasi (root project). Sesuaikan jika di-deploy
// pada sub-folder, mis. '/sitangkal/'.
define('BASE_URL', '/');

// =======================================================
// DATA SOURCE
// dummy  = menggunakan file JSON
// mysql  = menggunakan database MySQL
// =======================================================
define('DATA_MODE', 'mysql');

// =======================================================
// KONFIGURASI DATABASE (SATU-SATUNYA, dipakai semua modul)
// Ubah HANYA di sini jika kredensial DB berubah.
// =======================================================
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'db_sitangkal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =======================================================
// KONEKSI PDO (SINGLETON)
// Dipakai lewat function getPDO(), atau lewat variabel
// bantuan $config / $pdo (alias, demi kompatibilitas kode lama)
// =======================================================
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST .
                   ';port=' . DB_PORT .
                   ';dbname=' . DB_NAME .
                   ';charset=' . DB_CHARSET;

            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('<h2>Koneksi Database Gagal</h2><b>Error:</b> ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}

// Alias variabel supaya kode lama ($config->query(...), $pdo->query(...))
// tetap berjalan tanpa perlu diubah satu per satu.
$config = getPDO();
$pdo    = $config;

// =======================================================
// GEOSERVER
// =======================================================
define('GEOSERVER_BASE_URL', 'https://c-map.cimahikota.go.id/geoserver/ows');
define('GEOSERVER_WORKSPACE', 'cimahi');

// =======================================================
// BASEMAP CONFIG (dipakai oleh maps.php + assets/js/map.js)
// =======================================================
define('DEFAULT_BASEMAP', 'osm');
define('BASEMAP_CONFIG', [
    'osm' => [
        'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; OpenStreetMap contributors',
        'maxZoom' => 22,
        'maxNativeZoom' => 19,
    ],
    'satellite' => [
        'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        'attribution' => 'Tiles &copy; Esri',
        'maxZoom' => 22,
        'maxNativeZoom' => 19,
    ],
    'light' => [
        'url' => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
        'attribution' => '&copy; OpenStreetMap contributors &copy; CARTO',
        'maxZoom' => 22,
        'maxNativeZoom' => 19,
    ],
    'dark' => [
        'url' => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        'attribution' => '&copy; OpenStreetMap contributors &copy; CARTO',
        'maxZoom' => 22,
        'maxNativeZoom' => 19,
    ],
]);

// =======================================================
// AUTH HELPER (dimuat otomatis supaya class Auth selalu tersedia
// di seluruh aplikasi tanpa require terpisah-pisah)
// =======================================================
require_once __DIR__ . '/includes/auth.php';
