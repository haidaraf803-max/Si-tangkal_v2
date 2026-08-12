<?php
// 1. Muat konfigurasi tunggal aplikasi (koneksi DB, session, dsb).
//    Sebelumnya file ini bikin koneksi PDO sendiri dengan kredensial
//    ter-hardcode (host beda port, password beda) — jadi kalau kredensial
//    DB di config.php diubah, endpoint ini bisa gagal connect atau nyambung
//    ke DB yang salah tanpa disadari. Sekarang disamakan dengan pola yang
//    sudah dipakai di api/trees.php: 1 sumber koneksi untuk semua endpoint.
require_once __DIR__ . '/config.php';

// 2. Set headers for a JSON REST API and allow cross-origin requests (CORS)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    // Koneksi PDO singleton dari config.php (sama dengan seluruh aplikasi)
    $pdo = getPDO();
    // 3. Query your spatial data table
    // Replace 'locations' with your table name, and ensure you select your latitude and longitude columns
    $stmt = $pdo->query("SELECT *, koordinat_x as latitude, koordinat_y as longitude FROM pohon");
    
    // 4. Initialize the GeoJSON FeatureCollection structure
    $geojson = [
        "type" => "FeatureCollection",
        "features" => []
    ];
    
    // 5. Loop through rows and construct GeoJSON Features
    while ($row = $stmt->fetch()) {
        $feature = [
            "type" => "Feature",
            "geometry" => [
                "type" => "Point",
                // Crucial: GeoJSON coordinates must be ordered [longitude, latitude]
                "coordinates" => [
                    (float)$row['latitude'], 
                    (float)$row['longitude']
                ]
            ],
            // Add any database columns you want accessible as map properties here
            "properties" => [
                "id"          => $row['id'],
                "no_pohon"    => $row['no_pohon'],
                "nama_lokal"  => $row['nama_lokal'],
                "nama_latin"  => $row['nama_latin'],
                "family"      => $row['family'],
                "tahun_tanam" => $row['tahun_tanam'],
                "habitus"     => $row['habitus'],
                "status_kel"  => $row['status_kel'],
                "volume"      => $row['volume'],
                "kelas_awet"  => $row['kelas_awet'],
                "kelas_kuat"  => $row['kelas_kuat'],
                "berat_jenis" => $row['berat_jenis'],
                "kesehatan"   => $row['kesehatan'],
                "serapan_co"  => $row['serapan_co'],
                "produksi_o"  => $row['produksi_o'],
                "nama_jalan"  => $row['nama_jalan'],
                "kelurahan"   => $row['kelurahan'],
                "kecamatan"   => $row['kecamatan'],
                "keterangan"  => $row['keterangan'],
                "latitude"    => $row['latitude'],
                "longitude"   => $row['longitude'],
                "foto"        => $row['foto']
            ]
        ];
        
        // Push the individual feature into the master collection
        $geojson['features'][] = $feature;
    }
    
    // 6. Output the valid GeoJSON payload
    // JSON_NUMERIC_CHECK stops numbers from wrapping in quotation marks
    echo json_encode($geojson, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // Output database errors safely in JSON format
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed",
        "message" => $e->getMessage()
    ]);
}