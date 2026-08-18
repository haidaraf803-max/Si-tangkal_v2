<?php

/**
 * TarifModel — CRUD tabel `tarif_pergantian`
 * Dikelola manual oleh Superadmin/Admin karena dokumen kebutuhan
 * menyebut "kajian standar harga" tanpa melampirkan rumus resminya.
 * Formula yang dipakai PergantianModel: total = jumlah_pohon * diameter_cm * harga_per_cm
 */
class TarifModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        return $this->conn->query("SELECT * FROM tarif_pergantian ORDER BY jenis_pohon, diameter_min")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM tarif_pergantian WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Cari tarif yang cocok dgn jenis pohon (fallback ke tarif umum) & rentang diameter */
    public function findMatch(string $jenisPohon, float $diameter): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM tarif_pergantian
             WHERE jenis_pohon = :jenis AND :diameter BETWEEN diameter_min AND diameter_max
             LIMIT 1"
        );
        $stmt->execute([':jenis' => $jenisPohon, ':diameter' => $diameter]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }

        // fallback: tarif umum yang mencakup rentang diameter tsb
        $stmt = $this->conn->prepare(
            "SELECT * FROM tarif_pergantian
             WHERE :diameter BETWEEN diameter_min AND diameter_max
             ORDER BY (jenis_pohon = 'Umum / Tidak Diketahui') DESC
             LIMIT 1"
        );
        $stmt->execute([':diameter' => $diameter]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(string $jenis, float $min, float $max, float $harga, string $ket): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO tarif_pergantian (jenis_pohon, diameter_min, diameter_max, harga_per_cm, keterangan)
             VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$jenis, $min, $max, $harga, $ket]);
    }

    public function update(int $id, string $jenis, float $min, float $max, float $harga, string $ket): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE tarif_pergantian SET jenis_pohon=?, diameter_min=?, diameter_max=?, harga_per_cm=?, keterangan=? WHERE id=?"
        );
        return $stmt->execute([$jenis, $min, $max, $harga, $ket, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM tarif_pergantian WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

/**
 * PergantianModel — CRUD & perhitungan biaya pergantian pohon + data surat
 */
class PergantianModel
{
    private PDO $conn;
    private TarifModel $tarif;

    public function __construct(PDO $db)
    {
        $this->conn  = $db;
        $this->tarif = new TarifModel($db);
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT p.*, u.Name AS dibuat_oleh_nama
             FROM pergantian_pohon p
             LEFT JOIN t_users u ON u.UserId = p.dibuat_oleh
             ORDER BY p.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM pergantian_pohon WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Hitung & simpan data pergantian pohon.
     * Formula: total_biaya = jumlah_pohon * diameter_cm * harga_per_cm
     * (lihat catatan formula di TarifModel / db/migration_advanced.sql)
     */
    public function create(array $d, ?int $userId): int|false
    {
        $tarifRow = $this->tarif->findMatch($d['jenis_pohon'], (float) $d['diameter_cm']);
        $hargaPerCm = $tarifRow ? (float) $tarifRow['harga_per_cm'] : 0;
        $totalBiaya = $hargaPerCm * (float) $d['diameter_cm'] * (int) $d['jumlah_pohon'];

        $stmt = $this->conn->prepare(
            "INSERT INTO pergantian_pohon
                (pengajuan_id, pohon_id, jenis_pohon, diameter_cm, jumlah_pohon, tarif_id, harga_per_cm, total_biaya,
                 nomor_surat, tanggal_surat, nama_kabid, nip_kabid, dibuat_oleh)
             VALUES (:pengajuan_id, :pohon_id, :jenis, :diameter, :jumlah, :tarif_id, :harga, :total,
                     :no_surat, :tgl_surat, :nama_kabid, :nip_kabid, :user_id)"
        );
        $ok = $stmt->execute([
            ':pengajuan_id' => $d['pengajuan_id'] ?: null,
            ':pohon_id'     => $d['pohon_id'] ?: null,
            ':jenis'        => $d['jenis_pohon'],
            ':diameter'     => $d['diameter_cm'],
            ':jumlah'       => $d['jumlah_pohon'],
            ':tarif_id'     => $tarifRow['id'] ?? null,
            ':harga'        => $hargaPerCm,
            ':total'        => $totalBiaya,
            ':no_surat'     => $d['nomor_surat'] ?? null,
            ':tgl_surat'    => $d['tanggal_surat'] ?: date('Y-m-d'),
            ':nama_kabid'   => $d['nama_kabid'] ?? null,
            ':nip_kabid'    => $d['nip_kabid'] ?? null,
            ':user_id'      => $userId,
        ]);

        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    /**
     * Edit data pergantian pohon. Jika jenis_pohon/diameter_cm/jumlah_pohon berubah,
     * tarif & total_biaya dihitung ulang otomatis (formula sama seperti create()).
     */
    public function update(int $id, array $d): bool
    {
        $tarifRow   = $this->tarif->findMatch($d['jenis_pohon'], (float) $d['diameter_cm']);
        $hargaPerCm = $tarifRow ? (float) $tarifRow['harga_per_cm'] : 0;
        $totalBiaya = $hargaPerCm * (float) $d['diameter_cm'] * (int) $d['jumlah_pohon'];

        $stmt = $this->conn->prepare(
            "UPDATE pergantian_pohon SET
                jenis_pohon = :jenis,
                diameter_cm = :diameter,
                jumlah_pohon = :jumlah,
                tarif_id = :tarif_id,
                harga_per_cm = :harga,
                total_biaya = :total,
                nomor_surat = :no_surat,
                tanggal_surat = :tgl_surat,
                nama_kabid = :nama_kabid,
                nip_kabid = :nip_kabid
             WHERE id = :id"
        );
        return $stmt->execute([
            ':jenis'      => $d['jenis_pohon'],
            ':diameter'   => $d['diameter_cm'],
            ':jumlah'     => $d['jumlah_pohon'],
            ':tarif_id'   => $tarifRow['id'] ?? null,
            ':harga'      => $hargaPerCm,
            ':total'      => $totalBiaya,
            ':no_surat'   => $d['nomor_surat'] ?? null,
            ':tgl_surat'  => $d['tanggal_surat'] ?: date('Y-m-d'),
            ':nama_kabid' => $d['nama_kabid'] ?? null,
            ':nip_kabid'  => $d['nip_kabid'] ?? null,
            ':id'         => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM pergantian_pohon WHERE id = ?");
        return $stmt->execute([$id]);
    }
}