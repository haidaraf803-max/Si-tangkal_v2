<?php

/**
 * PengajuanModel
 * Kelas model untuk semua operasi CRUD pada tabel `pengajuan`.
 * Menggunakan PDO Prepared Statements untuk keamanan SQL Injection.
 */
class PengajuanModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Ambil semua data pengajuan, diurutkan dari terbaru.
     */
    public function getAll(): array
    {
        $stmt = $this->conn->query("SELECT * FROM pengajuan ORDER BY Id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil satu data pengajuan berdasarkan ID.
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM pengajuan WHERE Id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cari data pengajuan berdasarkan No Surat atau Nama Pemohon.
     */
    public function search(string $keyword): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM pengajuan
             WHERE No_Surat LIKE ? OR Nama_Pemohon LIKE ?
             ORDER BY Id DESC"
        );
        $like = "%{$keyword}%";
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil N data pengajuan terbaru untuk tampilan dashboard.
     */
    public function getLatest(int $limit = 10): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM pengajuan ORDER BY Id DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tambah data pengajuan baru.
     */
    public function create(
        string $no_surat,
        string $nama,
        string $lokasi,
        string $dokumentasi = ''
    ): bool {
        $sql = "INSERT INTO pengajuan (No_Surat, Nama_Pemohon, Lokasi_Pohon, Disposisi_Surat, Dokumentasi)
                VALUES (:no_surat, :nama, :lokasi, NOW(), :dokumentasi)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':no_surat'    => $no_surat,
            ':nama'        => $nama,
            ':lokasi'      => $lokasi,
            ':dokumentasi' => $dokumentasi,
        ]);
    }

    /**
     * Update data pengajuan secara lengkap.
     */
    public function update(
        int    $id,
        string $no_surat,
        string $nama,
        string $lokasi,
        string $disposisi,
        string $survey,
        string $tanggal,
        string $keterangan,
        string $dokumentasi,
        string $dokumentasiAfter = ''
    ): bool {
        $sql = "UPDATE pengajuan SET
                    No_Surat          = :no_surat,
                    Nama_Pemohon      = :nama,
                    Lokasi_Pohon      = :lokasi,
                    Disposisi_Surat   = :disposisi,
                    Survey_Pohon      = :survey,
                    Tanggal_Penanganan= :tanggal,
                    Keterangan        = :keterangan,
                    Dokumentasi       = :dokumentasi,
                    DokumentasiAfter  = :dokumentasiAfter
                WHERE Id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':no_surat'         => $no_surat,
            ':nama'             => $nama,
            ':lokasi'           => $lokasi,
            ':disposisi'        => $disposisi,
            ':survey'           => $survey,
            ':tanggal'          => $tanggal,
            ':keterangan'       => $keterangan,
            ':dokumentasi'      => $dokumentasi,
            ':dokumentasiAfter' => $dokumentasiAfter,
            ':id'               => $id,
        ]);
    }

    /**
     * Hapus data pengajuan berdasarkan ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM pengajuan WHERE Id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Hitung total seluruh pengajuan.
     */
    public function countAll(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM pengajuan")->fetchColumn();
    }

    /**
     * Hitung pengajuan yang belum diproses (Keterangan = 'Belum').
     */
    public function countBelumProses(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM pengajuan WHERE Keterangan = 'Belum' OR Keterangan IS NULL"
        )->fetchColumn();
    }
}
