<?php
/**
 * CEK KONEKSI DATABASE — Si-TANGKAL
 * ---------------------------------------------------------
 * Taruh file ini di folder root aplikasi
 * (C:\laragon\www\sitangkal-final\cek_koneksi_db.php)
 * lalu buka di browser: http://localhost/sitangkal-final/cek_koneksi_db.php
 *
 * Script ini TIDAK mengubah apapun, hanya menampilkan informasi
 * untuk mencari tahu kenapa tabel 'pohon' tidak ketemu.
 * Boleh dihapus setelah selesai debug.
 * ---------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<pre style='font-family:monospace; font-size:14px; background:#111; color:#0f0; padding:20px;'>";

echo "===================================================\n";
echo " CEK KONEKSI DATABASE - SI-TANGKAL\n";
echo "===================================================\n\n";

// 1. Cek konstanta di config.php
require_once __DIR__ . '/config.php';

echo "1) NILAI KONSTANTA DI config.php\n";
echo "-----------------------------------\n";
echo "DB_HOST    : " . DB_HOST . "\n";
echo "DB_PORT    : " . DB_PORT . "\n";
echo "DB_NAME    : " . DB_NAME . "\n";
echo "DB_USER    : " . DB_USER . "\n";
echo "DB_PASS    : " . (DB_PASS === '' ? '(kosong)' : '(ada, disembunyikan)') . "\n\n";

// 2. Info koneksi PDO yang sedang aktif (dari config.php, variabel $config)
echo "2) INFORMASI KONEKSI PDO YANG DIPAKAI APLIKASI\n";
echo "-----------------------------------\n";
try {
    $host_info = $config->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    $server_info = $config->getAttribute(PDO::ATTR_SERVER_INFO);
    $server_version = $config->getAttribute(PDO::ATTR_SERVER_VERSION);
    $client_version = $config->getAttribute(PDO::ATTR_CLIENT_VERSION);

    echo "Connection status : " . $host_info . "\n";
    echo "Server info       : " . $server_info . "\n";
    echo "Server version    : " . $server_version . "\n";
    echo "Client version    : " . $client_version . "\n\n";
} catch (Exception $e) {
    echo "Gagal ambil info koneksi: " . $e->getMessage() . "\n\n";
}

// 3. Database & port aktual yang dipakai koneksi ini (query langsung ke server)
echo "3) DATABASE & PORT MENURUT SERVER MYSQL ITU SENDIRI\n";
echo "-----------------------------------\n";
try {
    $stmt = $config->query("SELECT DATABASE() AS current_db, @@port AS mysql_port, @@version AS mysql_version, @@hostname AS mysql_hostname, @@datadir AS mysql_datadir");
    $row = $stmt->fetch();
    foreach ($row as $key => $val) {
        if (!is_int($key)) {
            echo str_pad($key, 18) . ": " . $val . "\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "Gagal query info server: " . $e->getMessage() . "\n\n";
}

// 4. Daftar SEMUA tabel yang terlihat oleh koneksi PHP ini
echo "4) DAFTAR TABEL YANG TERLIHAT OLEH PHP (via PDO ini)\n";
echo "-----------------------------------\n";
try {
    $stmt = $config->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "!!! TIDAK ADA TABEL SAMA SEKALI di database ini menurut PHP !!!\n";
        echo "Ini konfirmasi: PHP terhubung ke instance/database MySQL yang BEDA\n";
        echo "dari yang dipakai phpMyAdmin.\n";
    } else {
        foreach ($tables as $t) {
            echo "- " . $t . "\n";
        }
        echo "\nJumlah tabel: " . count($tables) . "\n";
        if (in_array('pohon', $tables)) {
            echo "\n>>> Tabel 'pohon' ADA di sini. Berarti error sebelumnya seharusnya sudah tidak muncul lagi.\n";
        } else {
            echo "\n>>> Tabel 'pohon' TIDAK ADA di daftar ini, walau tabel lain mungkin ada.\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "Gagal SHOW TABLES: " . $e->getMessage() . "\n\n";
}

// 5. Cek semua database yang ada di server yang sama (siapa tahu ada db_sitangkal ganda / typo)
echo "5) DAFTAR SEMUA DATABASE DI SERVER MYSQL YANG SAMA\n";
echo "-----------------------------------\n";
try {
    $stmt = $config->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dbs as $d) {
        $marker = ($d === DB_NAME) ? "  <-- ini yang dipakai config.php" : "";
        echo "- " . $d . $marker . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Gagal SHOW DATABASES: " . $e->getMessage() . "\n\n";
}

// 6. Info PHP & extension yang dipakai
echo "6) INFO PHP\n";
echo "-----------------------------------\n";
echo "PHP version       : " . phpversion() . "\n";
echo "PDO drivers       : " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "Loaded php.ini    : " . php_ini_loaded_file() . "\n";
echo "SAPI              : " . php_sapi_name() . "\n";

echo "</pre>";