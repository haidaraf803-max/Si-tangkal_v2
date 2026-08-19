<?php

/**
 * DeliniasiModel.php
 * ---------------------------------------------------------
 * Modul Peta sesuai dokumen kebutuhan:
 *   - Deliniasi RTH : setiap deliniasi dimasukkan, keluar
 *     persentase RTH-nya (otomatis, dihitung dari luas total
 *     seluruh poligon RTH dibagi luas wilayah Kota Cimahi).
 *   - Deliniasi Tajuk Pohon
 *   - Penanaman dan Potensi Penanaman (berdasarkan kajian
 *     potensi penanaman Kota Cimahi 2026, anggaran perubahan)
 *
 * Geometry disimpan sebagai GeoJSON polygon/point sederhana:
 *   [[lng,lat],[lng,lat],...]  (poligon, titik pertama = titik
 *   terakhir tidak wajib, akan otomatis ditutup saat hitung luas)
 * ---------------------------------------------------------
 */

/** Luas wilayah administratif Kota Cimahi (BPS/dokumen tata ruang).
 *  ~40,25 km2 = 40.250.000 m2. Bisa disesuaikan lewat konstanta ini
 *  jika ada angka resmi baru dari kajian RTH. */
if (!defined('CITY_AREA_M2')) {
    define('CITY_AREA_M2', 40250000.0);
}

/**
 * Menghitung luas poligon (lat/lng) dalam meter persegi memakai
 * proyeksi equirectangular sederhana (cukup akurat untuk area
 * seluas kota/kecamatan). $points: array of [lat, lng].
 */
function hitungLuasPoligonM2(array $points): float
{
    $n = count($points);
    if ($n < 3) {
        return 0.0;
    }

    // Titik acuan = titik pertama, proyeksikan semua titik ke bidang
    // datar (meter) relatif terhadap titik acuan.
    $latRef = $points[0][0];
    $R = 6378137.0; // radius bumi (m), WGS84
    $latRefRad = deg2rad($latRef);

    $xy = [];
    foreach ($points as $p) {
        [$lat, $lng] = $p;
        $x = deg2rad($lng - $points[0][1]) * $R * cos($latRefRad);
        $y = deg2rad($lat - $latRef) * $R;
        $xy[] = [$x, $y];
    }

    // Shoelace formula
    $area = 0.0;
    for ($i = 0; $i < $n; $i++) {
        [$x1, $y1] = $xy[$i];
        [$x2, $y2] = $xy[($i + 1) % $n];
        $area += ($x1 * $y2) - ($x2 * $y1);
    }
    return abs($area) / 2.0;
}

/** Parse geometry JSON (array of [lat,lng]) menjadi luas m2. 0 jika bukan poligon valid. */
function luasDariGeometryJson(string $geometryJson): float
{
    $points = json_decode($geometryJson, true);
    if (!is_array($points) || count($points) < 3) {
        return 0.0;
    }
    return hitungLuasPoligonM2($points);
}

/** DeliniasiRthModel — CRUD tabel `deliniasi_rth` + hitung % RTH kota */
class DeliniasiRthModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT d.*, u.Name AS dibuat_oleh_nama
             FROM deliniasi_rth d
             LEFT JOIN t_users u ON u.UserId = d.dibuat_oleh
             ORDER BY d.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM deliniasi_rth WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $d, ?int $userId): int|false
    {
        $luas = luasDariGeometryJson($d['geometry']);
        $stmt = $this->conn->prepare(
            "INSERT INTO deliniasi_rth (nama_lokasi, jenis_rth, kecamatan, geometry, luas_m2, keterangan, dibuat_oleh)
             VALUES (:nama, :jenis, :kec, :geo, :luas, :ket, :user)"
        );
        $ok = $stmt->execute([
            ':nama' => $d['nama_lokasi'], ':jenis' => $d['jenis_rth'] ?? null, ':kec' => $d['kecamatan'] ?? null,
            ':geo'  => $d['geometry'], ':luas' => $luas, ':ket' => $d['keterangan'] ?? null, ':user' => $userId,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    /** Update deliniasi RTH yang sudah ada. Luas dihitung ulang dari geometry baru. */
    public function update(int $id, array $d): bool
    {
        $luas = luasDariGeometryJson($d['geometry']);
        $stmt = $this->conn->prepare(
            "UPDATE deliniasi_rth
             SET nama_lokasi = :nama, jenis_rth = :jenis, kecamatan = :kec,
                 geometry = :geo, luas_m2 = :luas, keterangan = :ket
             WHERE id = :id"
        );
        return $stmt->execute([
            ':nama' => $d['nama_lokasi'], ':jenis' => $d['jenis_rth'] ?? null, ':kec' => $d['kecamatan'] ?? null,
            ':geo'  => $d['geometry'], ':luas' => $luas, ':ket' => $d['keterangan'] ?? null, ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM deliniasi_rth WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function totalLuasM2(): float
    {
        return (float) $this->conn->query("SELECT COALESCE(SUM(luas_m2),0) FROM deliniasi_rth")->fetchColumn();
    }

    /** Persentase RTH kota = total luas deliniasi RTH / luas wilayah kota * 100 */
    public function persentaseRth(): float
    {
        $total = $this->totalLuasM2();
        if (CITY_AREA_M2 <= 0) {
            return 0.0;
        }
        return round(($total / CITY_AREA_M2) * 100, 2);
    }
}

/** DeliniasiTajukModel — CRUD tabel `deliniasi_tajuk` */
class DeliniasiTajukModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT t.*, u.Name AS dibuat_oleh_nama
             FROM deliniasi_tajuk t
             LEFT JOIN t_users u ON u.UserId = t.dibuat_oleh
             ORDER BY t.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM deliniasi_tajuk WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $d, ?int $userId): int|false
    {
        $luas = luasDariGeometryJson($d['geometry']);
        $stmt = $this->conn->prepare(
            "INSERT INTO deliniasi_tajuk (pohon_id, nama_lokasi, geometry, luas_m2, keterangan, dibuat_oleh)
             VALUES (:pohon, :nama, :geo, :luas, :ket, :user)"
        );
        $ok = $stmt->execute([
            ':pohon' => $d['pohon_id'] ?: null, ':nama' => $d['nama_lokasi'], ':geo' => $d['geometry'],
            ':luas'  => $luas, ':ket' => $d['keterangan'] ?? null, ':user' => $userId,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    /** Update deliniasi tajuk pohon yang sudah ada. Luas dihitung ulang dari geometry baru. */
    public function update(int $id, array $d): bool
    {
        $luas = luasDariGeometryJson($d['geometry']);
        $stmt = $this->conn->prepare(
            "UPDATE deliniasi_tajuk
             SET pohon_id = :pohon, nama_lokasi = :nama, geometry = :geo, luas_m2 = :luas, keterangan = :ket
             WHERE id = :id"
        );
        return $stmt->execute([
            ':pohon' => $d['pohon_id'] ?: null, ':nama' => $d['nama_lokasi'], ':geo' => $d['geometry'],
            ':luas'  => $luas, ':ket' => $d['keterangan'] ?? null, ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM deliniasi_tajuk WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function totalLuasM2(): float
    {
        return (float) $this->conn->query("SELECT COALESCE(SUM(luas_m2),0) FROM deliniasi_tajuk")->fetchColumn();
    }
}

/** PotensiPenanamanModel — CRUD tabel `potensi_penanaman` (realisasi & potensi) */
class PotensiPenanamanModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(?string $tipe = null): array
    {
        $sql = "SELECT p.*, u.Name AS dibuat_oleh_nama FROM potensi_penanaman p
                LEFT JOIN t_users u ON u.UserId = p.dibuat_oleh";
        $params = [];
        if ($tipe) {
            $sql .= " WHERE p.tipe = :tipe";
            $params[':tipe'] = $tipe;
        }
        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM potensi_penanaman WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $d, ?int $userId): int|false
    {
        $geomArr = json_decode($d['geometry'], true);
        $isPolygon = is_array($geomArr) && count($geomArr) >= 3;
        $luas = $isPolygon ? luasDariGeometryJson($d['geometry']) : null;

        $stmt = $this->conn->prepare(
            "INSERT INTO potensi_penanaman
                (tipe, nama_lokasi, kecamatan, geometry, luas_m2, estimasi_jumlah_pohon, sumber_kajian, keterangan, dibuat_oleh)
             VALUES
                (:tipe, :nama, :kec, :geo, :luas, :estimasi, :sumber, :ket, :user)"
        );
        $ok = $stmt->execute([
            ':tipe'     => $d['tipe'] === 'realisasi' ? 'realisasi' : 'potensi',
            ':nama'     => $d['nama_lokasi'],
            ':kec'      => $d['kecamatan'] ?? null,
            ':geo'      => $d['geometry'],
            ':luas'     => $luas,
            ':estimasi' => $d['estimasi_jumlah_pohon'] !== '' ? (int) $d['estimasi_jumlah_pohon'] : null,
            ':sumber'   => $d['sumber_kajian'] ?: 'Kajian Potensi Penanaman Kota Cimahi 2026 (Anggaran Perubahan)',
            ':ket'      => $d['keterangan'] ?? null,
            ':user'     => $userId,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    /** Update data penanaman/potensi yang sudah ada. Luas dihitung ulang jika geometry berupa poligon. */
    public function update(int $id, array $d): bool
    {
        $geomArr = json_decode($d['geometry'], true);
        $isPolygon = is_array($geomArr) && count($geomArr) >= 3;
        $luas = $isPolygon ? luasDariGeometryJson($d['geometry']) : null;

        $stmt = $this->conn->prepare(
            "UPDATE potensi_penanaman
             SET tipe = :tipe, nama_lokasi = :nama, kecamatan = :kec, geometry = :geo, luas_m2 = :luas,
                 estimasi_jumlah_pohon = :estimasi, sumber_kajian = :sumber, keterangan = :ket
             WHERE id = :id"
        );
        return $stmt->execute([
            ':tipe'     => $d['tipe'] === 'realisasi' ? 'realisasi' : 'potensi',
            ':nama'     => $d['nama_lokasi'],
            ':kec'      => $d['kecamatan'] ?? null,
            ':geo'      => $d['geometry'],
            ':luas'     => $luas,
            ':estimasi' => $d['estimasi_jumlah_pohon'] !== '' ? (int) $d['estimasi_jumlah_pohon'] : null,
            ':sumber'   => $d['sumber_kajian'] ?: 'Kajian Potensi Penanaman Kota Cimahi 2026 (Anggaran Perubahan)',
            ':ket'      => $d['keterangan'] ?? null,
            ':id'       => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM potensi_penanaman WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function countByTipe(string $tipe): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM potensi_penanaman WHERE tipe = ?");
        $stmt->execute([$tipe]);
        return (int) $stmt->fetchColumn();
    }
}