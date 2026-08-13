<?php

/**
 * PenanamanModel.php
 * ---------------------------------------------------------
 * LaporanPenanamanModel — CRUD tabel `laporan_penanaman`.
 * Sesuai dokumen kebutuhan: "Input penanaman: tanggal, lokasi,
 * koordinat lokasi, asal bibit, jumlah bibit, keterangan"
 * (Petugas Penanaman — Pak Ahmad). Terpisah dari permintaan
 * bibit (lihat permohonan_bibit.php).
 * ---------------------------------------------------------
 */
class LaporanPenanamanModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(string $keyword = ''): array
    {
        $sql = "SELECT lp.*, u.Name AS petugas_nama
                FROM laporan_penanaman lp
                LEFT JOIN t_users u ON u.UserId = lp.petugas_id";
        $params = [];
        if ($keyword !== '') {
            $sql .= " WHERE lp.lokasi LIKE :kw OR lp.asal_bibit LIKE :kw OR lp.jenis_tanaman LIKE :kw";
            $params[':kw'] = '%' . $keyword . '%';
        }
        $sql .= " ORDER BY lp.tanggal DESC, lp.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM laporan_penanaman WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countAll(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM laporan_penanaman")->fetchColumn();
    }

    public function totalBibitTertanam(): int
    {
        return (int) $this->conn->query("SELECT COALESCE(SUM(jumlah_bibit),0) FROM laporan_penanaman")->fetchColumn();
    }

    public function create(array $d, ?int $petugasId): int|false
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO laporan_penanaman
                (tanggal, lokasi, latitude, longitude, asal_bibit, jenis_tanaman, jumlah_bibit, keterangan, foto, petugas_id)
             VALUES
                (:tanggal, :lokasi, :lat, :lng, :asal, :jenis, :jumlah, :ket, :foto, :petugas)"
        );
        $ok = $stmt->execute([
            ':tanggal' => $d['tanggal'],
            ':lokasi'  => $d['lokasi'],
            ':lat'     => $d['latitude'] !== '' ? $d['latitude'] : null,
            ':lng'     => $d['longitude'] !== '' ? $d['longitude'] : null,
            ':asal'    => $d['asal_bibit'],
            ':jenis'   => $d['jenis_tanaman'] ?? null,
            ':jumlah'  => (int) ($d['jumlah_bibit'] ?? 0),
            ':ket'     => $d['keterangan'] ?? null,
            ':foto'    => $d['foto'] ?? null,
            ':petugas' => $petugasId,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    public function update(int $id, array $d): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE laporan_penanaman SET
                tanggal=:tanggal, lokasi=:lokasi, latitude=:lat, longitude=:lng,
                asal_bibit=:asal, jenis_tanaman=:jenis, jumlah_bibit=:jumlah, keterangan=:ket
             WHERE id = :id"
        );
        return $stmt->execute([
            ':tanggal' => $d['tanggal'],
            ':lokasi'  => $d['lokasi'],
            ':lat'     => $d['latitude'] !== '' ? $d['latitude'] : null,
            ':lng'     => $d['longitude'] !== '' ? $d['longitude'] : null,
            ':asal'    => $d['asal_bibit'],
            ':jenis'   => $d['jenis_tanaman'] ?? null,
            ':jumlah'  => (int) ($d['jumlah_bibit'] ?? 0),
            ':ket'     => $d['keterangan'] ?? null,
            ':id'      => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM laporan_penanaman WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
