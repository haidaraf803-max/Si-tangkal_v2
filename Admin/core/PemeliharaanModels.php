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

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT p.*, u.Name AS petugas_nama
             FROM pemakaian_pupuk p
             LEFT JOIN t_users u ON u.UserId = p.petugas_id
             ORDER BY p.tanggal DESC, p.id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT b.*, u.Name AS petugas_nama
             FROM pemakaian_bbm b
             LEFT JOIN t_users u ON u.UserId = b.petugas_id
             ORDER BY b.tanggal DESC, b.id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                 jenis_bbm, jumlah_liter, nominal_rupiah, kendaraan, petugas_id, keterangan, bukti)
             VALUES
                (:tanggal, :terima_dari, :penerima, :keperluan, :jumlah_kupon, :nominal_kupon,
                 :jenis, :liter, :nominal, :kendaraan, :petugas, :ket, :bukti)"
        );
        $ok = $stmt->execute([
            ':tanggal'       => $d['tanggal'],
            ':terima_dari'   => $d['terima_dari'] ?? null,
            ':penerima'      => $d['penerima'] ?? null,
            ':keperluan'     => $d['keperluan'] ?? null,
            ':jumlah_kupon'  => $d['jumlah_kupon'] !== '' ? (int) $d['jumlah_kupon'] : null,
            ':nominal_kupon' => $d['nominal_kupon'] !== '' ? (float) $d['nominal_kupon'] : null,
            ':jenis'         => $d['jenis_bbm'] ?: null,
            ':liter'         => $d['jumlah_liter'] !== '' ? (float) $d['jumlah_liter'] : null,
            ':nominal'       => $d['nominal_rupiah'] ?: 0,
            ':kendaraan'     => $d['kendaraan'] ?? null,
            ':petugas'       => $petugasId,
            ':ket'           => $d['keterangan'] ?? null,
            ':bukti'         => $d['bukti'] ?? null,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    public function update(int $id, array $d): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE pemakaian_bbm SET
                tanggal=:tanggal, terima_dari=:terima_dari, penerima=:penerima,
                keperluan=:keperluan, jumlah_kupon=:jumlah_kupon, nominal_kupon=:nominal_kupon,
                keterangan=:ket
             WHERE id = :id"
        );
        return $stmt->execute([
            ':tanggal'       => $d['tanggal'],
            ':terima_dari'   => $d['terima_dari'] ?? null,
            ':penerima'      => $d['penerima'] ?? null,
            ':keperluan'     => $d['keperluan'] ?? null,
            ':jumlah_kupon'  => $d['jumlah_kupon'] !== '' ? (int) $d['jumlah_kupon'] : null,
            ':nominal_kupon' => $d['nominal_kupon'] !== '' ? (float) $d['nominal_kupon'] : null,
            ':ket'           => $d['keterangan'] ?? null,
            ':id'            => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM pemakaian_bbm WHERE id = ?");
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
