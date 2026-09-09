<?php

/**
 * PupukModel — CRUD tabel `pemakaian_pupuk`
 * (Petugas Pemeliharaan RTH input pemakaian pupuk, sesuai dokumen kebutuhan)
 */
class PupukModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /** @param array $filter ['jenis' => ''] filter jenis pupuk dari entri yang ada */
    public function getAll(array $filter = []): array
    {
        $jenis = trim($filter['jenis'] ?? '');
        $sql = "SELECT p.*, u.Name AS petugas_nama
                FROM pemakaian_pupuk p
                LEFT JOIN t_users u ON u.UserId = p.petugas_id
                WHERE 1=1";
        $params = [];
        if ($jenis !== '') {
            $sql .= " AND p.jenis_pupuk = :jenis";
            $params[':jenis'] = $jenis;
        }
        $sql .= " ORDER BY p.tanggal DESC, p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableJenis(): array
    {
        $stmt = $this->conn->query(
            "SELECT DISTINCT jenis_pupuk FROM pemakaian_pupuk WHERE jenis_pupuk IS NOT NULL AND jenis_pupuk <> '' ORDER BY jenis_pupuk"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM pemakaian_pupuk WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $d, ?int $petugasId): int|false
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO pemakaian_pupuk (tanggal, jenis_pupuk, jumlah, satuan, lokasi, petugas_id, keterangan, foto)
             VALUES (:tanggal, :jenis, :jumlah, :satuan, :lokasi, :petugas, :ket, :foto)"
        );
        $ok = $stmt->execute([
            ':tanggal' => $d['tanggal'], ':jenis' => $d['jenis_pupuk'], ':jumlah' => $d['jumlah'],
            ':satuan'  => $d['satuan'] ?: 'kg', ':lokasi' => $d['lokasi'], ':petugas' => $petugasId,
            ':ket'     => $d['keterangan'] ?? null, ':foto' => $d['foto'] ?? null,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    public function update(int $id, array $d): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE pemakaian_pupuk SET tanggal=:tanggal, jenis_pupuk=:jenis, jumlah=:jumlah,
                    satuan=:satuan, lokasi=:lokasi, keterangan=:ket WHERE id = :id"
        );
        return $stmt->execute([
            ':tanggal' => $d['tanggal'], ':jenis' => $d['jenis_pupuk'], ':jumlah' => $d['jumlah'],
            ':satuan'  => $d['satuan'] ?: 'kg', ':lokasi' => $d['lokasi'], ':ket' => $d['keterangan'] ?? null,
            ':id'      => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM pemakaian_pupuk WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

/**
 * BbmModel — CRUD tabel `pemakaian_bbm`
 * Field mengikuti dokumen kebutuhan: terima dari, penerima, tanggal,
 * keperluan, jumlah kupon, nominal kupon. Kolom lama (jenis_bbm,
 * jumlah_liter, kendaraan) tetap didukung untuk kompatibilitas data
 * lama, tapi tidak lagi wajib.
 */
class BbmModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * @param array $filter ['dari' => 'Y-m-d', 'sampai' => 'Y-m-d', 'tahun' => '']
     *   'dari'/'sampai' menyaring berdasarkan tanggal terima; 'tahun' menyaring
     *   berdasarkan YEAR(tanggal) dan dipakai juga untuk menghitung total nominal per tahun.
     */
    public function getAll(array $filter = []): array
    {
        $dari   = trim($filter['dari'] ?? '');
        $sampai = trim($filter['sampai'] ?? '');
        $tahun  = trim($filter['tahun'] ?? '');

        $sql = "SELECT b.*, u.Name AS petugas_nama
                FROM pemakaian_bbm b
                LEFT JOIN t_users u ON u.UserId = b.petugas_id
                WHERE 1=1";
        $params = [];
        if ($dari !== '') {
            $sql .= " AND b.tanggal >= :dari";
            $params[':dari'] = $dari;
        }
        if ($sampai !== '') {
            $sql .= " AND b.tanggal <= :sampai";
            $params[':sampai'] = $sampai;
        }
        if ($tahun !== '') {
            $sql .= " AND YEAR(b.tanggal) = :tahun";
            $params[':tahun'] = (int) $tahun;
        }
        $sql .= " ORDER BY b.tanggal DESC, b.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableYears(): array
    {
        $stmt = $this->conn->query("SELECT DISTINCT YEAR(tanggal) AS th FROM pemakaian_bbm ORDER BY th DESC");
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM pemakaian_bbm WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $d, ?int $petugasId): int|false
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO pemakaian_bbm
                (tanggal, terima_dari, penerima, keperluan, jumlah_kupon, nominal_kupon,
                 jumlah_kupon_pelumas, nominal_kupon_pelumas,
                 jenis_bbm, jumlah_liter, nominal_rupiah, kendaraan, petugas_id, keterangan, bukti)
             VALUES
                (:tanggal, :terima_dari, :penerima, :keperluan, :jumlah_kupon, :nominal_kupon,
                 :jumlah_kupon_pelumas, :nominal_kupon_pelumas,
                 :jenis, :liter, :nominal, :kendaraan, :petugas, :ket, :bukti)"
        );
        $ok = $stmt->execute([
            ':tanggal'               => $d['tanggal'],
            ':terima_dari'           => $d['terima_dari'] ?? null,
            ':penerima'              => $d['penerima'] ?? null,
            ':keperluan'             => $d['keperluan'] ?? null,
            ':jumlah_kupon'          => $d['jumlah_kupon'] !== '' ? (int) $d['jumlah_kupon'] : null,
            ':nominal_kupon'         => $d['nominal_kupon'] !== '' ? (float) $d['nominal_kupon'] : null,
            ':jumlah_kupon_pelumas'  => ($d['jumlah_kupon_pelumas'] ?? '') !== '' ? (int) $d['jumlah_kupon_pelumas'] : null,
            ':nominal_kupon_pelumas' => ($d['nominal_kupon_pelumas'] ?? '') !== '' ? (float) $d['nominal_kupon_pelumas'] : null,
            ':jenis'                 => $d['jenis_bbm'] ?: null,
            ':liter'                 => $d['jumlah_liter'] !== '' ? (float) $d['jumlah_liter'] : null,
            ':nominal'               => $d['nominal_rupiah'] ?: 0,
            ':kendaraan'             => $d['kendaraan'] ?? null,
            ':petugas'               => $petugasId,
            ':ket'                   => $d['keterangan'] ?? null,
            ':bukti'                 => $d['bukti'] ?? null,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    public function update(int $id, array $d): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE pemakaian_bbm SET
                tanggal=:tanggal, terima_dari=:terima_dari, penerima=:penerima,
                keperluan=:keperluan, jumlah_kupon=:jumlah_kupon, nominal_kupon=:nominal_kupon,
                jumlah_kupon_pelumas=:jumlah_kupon_pelumas, nominal_kupon_pelumas=:nominal_kupon_pelumas,
                jenis_bbm=:jenis, kendaraan=:kendaraan,
                keterangan=:ket
             WHERE id = :id"
        );
        return $stmt->execute([
            ':tanggal'               => $d['tanggal'],
            ':terima_dari'           => $d['terima_dari'] ?? null,
            ':penerima'              => $d['penerima'] ?? null,
            ':keperluan'             => $d['keperluan'] ?? null,
            ':jumlah_kupon'          => $d['jumlah_kupon'] !== '' ? (int) $d['jumlah_kupon'] : null,
            ':nominal_kupon'         => $d['nominal_kupon'] !== '' ? (float) $d['nominal_kupon'] : null,
            ':jumlah_kupon_pelumas'  => ($d['jumlah_kupon_pelumas'] ?? '') !== '' ? (int) $d['jumlah_kupon_pelumas'] : null,
            ':nominal_kupon_pelumas' => ($d['nominal_kupon_pelumas'] ?? '') !== '' ? (float) $d['nominal_kupon_pelumas'] : null,
            ':jenis'                 => $d['jenis_bbm'] ?: null,
            ':kendaraan'             => $d['kendaraan'] ?? null,
            ':ket'                   => $d['keterangan'] ?? null,
            ':id'                    => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM pemakaian_bbm WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

/**
 * PemeliharaanRthModel — CRUD tabel `pemeliharaan_rth`
 * Menu baru (poin 3e dokumen skema 20 Agustus 2026):
 * "Input laporan pemeliharaan RTH: personil, tanggal, lokasi,
 * kegiatan, foto (wajib)".
 */
class PemeliharaanRthModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT r.*, u.Name AS petugas_nama
             FROM pemeliharaan_rth r
             LEFT JOIN t_users u ON u.UserId = r.petugas_id
             ORDER BY r.tanggal DESC, r.id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM pemeliharaan_rth WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $d, ?int $petugasId): int|false
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO pemeliharaan_rth (tanggal, personil, lokasi, kegiatan, keterangan, foto, petugas_id)
             VALUES (:tanggal, :personil, :lokasi, :kegiatan, :ket, :foto, :petugas)"
        );
        return $stmt->execute([
            ':tanggal'  => $d['tanggal'],
            ':personil' => $d['personil'],
            ':lokasi'   => $d['lokasi'],
            ':kegiatan' => $d['kegiatan'],
            ':ket'      => $d['keterangan'] ?? null,
            ':foto'     => $d['foto'],
            ':petugas'  => $petugasId,
        ]) ? (int) $this->conn->lastInsertId() : false;
    }

    public function update(int $id, array $d): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE pemeliharaan_rth SET
                tanggal=:tanggal, personil=:personil, lokasi=:lokasi,
                kegiatan=:kegiatan, keterangan=:ket
             WHERE id = :id"
        );
        return $stmt->execute([
            ':tanggal'  => $d['tanggal'],
            ':personil' => $d['personil'],
            ':lokasi'   => $d['lokasi'],
            ':kegiatan' => $d['kegiatan'],
            ':ket'      => $d['keterangan'] ?? null,
            ':id'       => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM pemeliharaan_rth WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

/**
 * SarprasModel — CRUD tabel `permintaan_sarpras`
 */
class SarprasModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.*, u.Name AS pemohon_nama
             FROM permintaan_sarpras s
             LEFT JOIN t_users u ON u.UserId = s.pemohon_id
             ORDER BY s.tanggal DESC, s.id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM permintaan_sarpras WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $d, ?int $pemohonId): int|false
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO permintaan_sarpras
                (tanggal, terima_dari, penerima, nama_barang, jumlah, satuan, alasan, pemohon_id)
             VALUES
                (:tanggal, :terima_dari, :penerima, :nama, :jumlah, :satuan, :alasan, :pemohon)"
        );
        $ok = $stmt->execute([
            ':tanggal'     => $d['tanggal'],
            ':terima_dari' => $d['terima_dari'] ?? null,
            ':penerima'    => $d['penerima'] ?? null,
            ':nama'        => $d['nama_barang'],
            ':jumlah'      => $d['jumlah'] ?: 1,
            ':satuan'      => $d['satuan'] ?: 'unit',
            ':alasan'      => $d['alasan'] ?? null,
            ':pemohon'     => $pemohonId,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    /** Edit data permintaan (tanggal, terima dari, penerima, barang, jumlah, satuan, alasan) */
    public function update(int $id, array $d): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE permintaan_sarpras SET
                tanggal = :tanggal,
                terima_dari = :terima_dari,
                penerima = :penerima,
                nama_barang = :nama,
                jumlah = :jumlah,
                satuan = :satuan,
                alasan = :alasan
             WHERE id = :id"
        );
        return $stmt->execute([
            ':tanggal'     => $d['tanggal'],
            ':terima_dari' => $d['terima_dari'] ?? null,
            ':penerima'    => $d['penerima'] ?? null,
            ':nama'        => $d['nama_barang'],
            ':jumlah'      => $d['jumlah'] ?: 1,
            ':satuan'      => $d['satuan'] ?: 'unit',
            ':alasan'      => $d['alasan'] ?? null,
            ':id'          => $id,
        ]);
    }

    /** Admin/Superadmin menanggapi status permintaan */
    public function tanggapi(int $id, string $status, string $catatan): bool
    {
        $allowed = ['diajukan', 'disetujui', 'ditolak', 'selesai'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $stmt = $this->conn->prepare(
            "UPDATE permintaan_sarpras SET status = :status, catatan_admin = :catatan WHERE id = :id"
        );
        return $stmt->execute([':status' => $status, ':catatan' => $catatan, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM permintaan_sarpras WHERE id = ?");
        return $stmt->execute([$id]);
    }
}