<?php

/**
 * PenanamanModel.php
 * ---------------------------------------------------------
 * LaporanPenanamanModel — CRUD tabel `laporan_penanaman`.
 * Sesuai dokumen kebutuhan: "Input penanaman: tanggal, lokasi,
 * koordinat lokasi, asal bibit, jumlah bibit, keterangan"
 * (Petugas Penanaman — Pak Ahmad). Terpisah dari permintaan
 * bibit (lihat permohonan_bibit.php).
 *
 * Update 20 Agustus 2026 (Menu Pemeliharaan poin a):
 *  - createBatch(): satu form bisa memasukkan banyak baris
 *    (jenis tanaman + sumber bibit + jumlah) sekaligus, berbagi
 *    tanggal/lokasi/koordinat/foto/keterangan yang sama.
 *  - getAll() menerima filter tahun, sumber (asal_bibit), dan
 *    jenis (jenis_tanaman) supaya bisa tahu jumlah entry per filter.
 *  - getAvailableYears()/getAvailableSumber()/getAvailableJenis()
 *    untuk mengisi dropdown filter dari data yang benar-benar ada.
 * ---------------------------------------------------------
 */
class LaporanPenanamanModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * @param array $filter ['keyword' => '', 'tahun' => '', 'sumber' => '', 'jenis' => '']
     */
    public function getAll(array $filter = []): array
    {
        $keyword = trim($filter['keyword'] ?? '');
        $tahun   = trim($filter['tahun'] ?? '');
        $sumber  = trim($filter['sumber'] ?? '');
        $jenis   = trim($filter['jenis'] ?? '');

        $sql = "SELECT lp.*, u.Name AS petugas_nama
                FROM laporan_penanaman lp
                LEFT JOIN t_users u ON u.UserId = lp.petugas_id
                WHERE 1=1";
        $params = [];

        if ($keyword !== '') {
            $sql .= " AND (lp.lokasi LIKE :kw OR lp.asal_bibit LIKE :kw OR lp.jenis_tanaman LIKE :kw)";
            $params[':kw'] = '%' . $keyword . '%';
        }
        if ($tahun !== '') {
            $sql .= " AND YEAR(lp.tanggal) = :tahun";
            $params[':tahun'] = (int) $tahun;
        }
        if ($sumber !== '') {
            $sql .= " AND lp.asal_bibit = :sumber";
            $params[':sumber'] = $sumber;
        }
        if ($jenis !== '') {
            $sql .= " AND lp.jenis_tanaman = :jenis";
            $params[':jenis'] = $jenis;
        }

        $sql .= " ORDER BY lp.tanggal DESC, lp.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableYears(): array
    {
        $stmt = $this->conn->query(
            "SELECT DISTINCT YEAR(tanggal) AS th FROM laporan_penanaman ORDER BY th DESC"
        );
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getAvailableSumber(): array
    {
        $stmt = $this->conn->query(
            "SELECT DISTINCT asal_bibit FROM laporan_penanaman WHERE asal_bibit IS NOT NULL AND asal_bibit <> '' ORDER BY asal_bibit"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableJenis(): array
    {
        $stmt = $this->conn->query(
            "SELECT DISTINCT jenis_tanaman FROM laporan_penanaman WHERE jenis_tanaman IS NOT NULL AND jenis_tanaman <> '' ORDER BY jenis_tanaman"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

    /**
     * Simpan banyak baris (jenis tanaman + sumber bibit + jumlah) sekaligus,
     * berbagi tanggal/lokasi/koordinat/foto/keterangan yang sama.
     *
     * @param array $rows [['jenis_tanaman'=>'', 'asal_bibit'=>'', 'jumlah_bibit'=>0], ...]
     * @param array $shared ['tanggal','lokasi','latitude','longitude','foto','keterangan']
     * @return int jumlah baris yang berhasil disimpan
     */
    public function createBatch(array $rows, array $shared, ?int $petugasId): int
    {
        $saved = 0;
        $this->conn->beginTransaction();
        try {
            foreach ($rows as $row) {
                $jenis  = trim($row['jenis_tanaman'] ?? '');
                $asal   = trim($row['asal_bibit'] ?? '');
                $jumlah = (int) ($row['jumlah_bibit'] ?? 0);
                if ($jenis === '' && $asal === '' && $jumlah <= 0) {
                    continue; // baris kosong, lewati
                }
                $ok = $this->create([
                    'tanggal'       => $shared['tanggal'],
                    'lokasi'        => $shared['lokasi'],
                    'latitude'      => $shared['latitude'] ?? '',
                    'longitude'     => $shared['longitude'] ?? '',
                    'asal_bibit'    => $asal,
                    'jenis_tanaman' => $jenis,
                    'jumlah_bibit'  => $jumlah,
                    'keterangan'    => $shared['keterangan'] ?? '',
                    'foto'          => $shared['foto'] ?? null,
                ], $petugasId);
                if ($ok !== false) {
                    $saved++;
                }
            }
            $this->conn->commit();
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
        return $saved;
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