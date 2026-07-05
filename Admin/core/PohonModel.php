<?php

/**
 * PohonModel
 * Kelas model untuk semua operasi CRUD pada tabel `pohon`.
 * Menggunakan PDO Prepared Statements untuk keamanan SQL Injection.
 */
class PohonModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Ambil semua data pohon, diurutkan dari terbaru.
     */
    public function getAll(): array
    {
        $stmt = $this->conn->query("SELECT * FROM pohon ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil satu data pohon berdasarkan ID.
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM pohon WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cari data pohon berdasarkan nama pohon atau lokasi.
     */
    public function search(string $keyword): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM pohon
             WHERE nama_lokal LIKE ? OR nama_jalan LIKE ? OR kesehatan LIKE ?
             ORDER BY id DESC"
        );
        $like = "%{$keyword}%";
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil N data pohon terbaru untuk tampilan dashboard.
     */
    public function getLatest(int $limit = 10): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM pohon ORDER BY id DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tambah data pohon baru.
     */
public function create(
    string $nama_lokal,
    string $nama_latin,
    string $family,
    string $tahun_tanam,
    string $habitus,
    string $status_kel,
    float $volume,
    string $kelas_awet,
    string $kelas_kuat,
    float $berat_jenis,
    string $kesehatan,
    float $serapan_co,
    float $produksi_o,
    string $nama_jalan,
    string $kelurahan,
    string $kecamatan,
    float $koordinat_x,
    float $koordinat_y,
    string $keterangan
): bool {

    $sql = "INSERT INTO pohon (
        nama_lokal,
        nama_latin,
        family,
        tahun_tanam,
        habitus,
        status_kel,
        volume,
        kelas_awet,
        kelas_kuat,
        berat_jenis,
        kesehatan,
        serapan_co,
        produksi_o,
        nama_jalan,
        kelurahan,
        kecamatan,
        koordinat_x,
        koordinat_y,
        keterangan
    ) VALUES (
        :nama_lokal,
        :nama_latin,
        :family,
        :tahun_tanam,
        :habitus,
        :status_kel,
        :volume,
        :kelas_awet,
        :kelas_kuat,
        :berat_jenis,
        :kesehatan,
        :serapan_co,
        :produksi_o,
        :nama_jalan,
        :kelurahan,
        :kecamatan,
        :koordinat_x,
        :koordinat_y,
        :keterangan
    )";

    $stmt = $this->conn->prepare($sql);

    return $stmt->execute([
        ':nama_lokal'  => $nama_lokal,
        ':nama_latin'  => $nama_latin,
        ':family'      => $family,
        ':tahun_tanam' => $tahun_tanam,
        ':habitus'     => $habitus,
        ':status_kel'  => $status_kel,
        ':volume'      => $volume,
        ':kelas_awet'  => $kelas_awet,
        ':kelas_kuat'  => $kelas_kuat,
        ':berat_jenis' => $berat_jenis,
        ':kesehatan'   => $kesehatan,
        ':serapan_co'  => $serapan_co,
        ':produksi_o'  => $produksi_o,
        ':nama_jalan'  => $nama_jalan,
        ':kelurahan'   => $kelurahan,
        ':kecamatan'   => $kecamatan,
        ':koordinat_x' => $koordinat_x,
        ':koordinat_y' => $koordinat_y,
        ':keterangan'  => $keterangan,
    ]);
}

    /**
     * Update data pohon secara lengkap.
     */
   public function update(
    int $id,
    string $nama_lokal,
    string $nama_latin,
    string $family,
    string $tahun_tanam,
    string $habitus,
    string $status_kel,
    float $volume,
    string $kelas_awet,
    string $kelas_kuat,
    float $berat_jenis,
    string $kesehatan,
    float $serapan_co,
    float $produksi_o,
    string $nama_jalan,
    string $kelurahan,
    string $kecamatan,
    float $koordinat_x,
    float $koordinat_y,
    string $keterangan
): bool {

    $sql = "UPDATE pohon SET
        nama_lokal = :nama_lokal,
        nama_latin = :nama_latin,
        family = :family,
        tahun_tanam = :tahun_tanam,
        habitus = :habitus,
        status_kel = :status_kel,
        volume = :volume,
        kelas_awet = :kelas_awet,
        kelas_kuat = :kelas_kuat,
        berat_jenis = :berat_jenis,
        kesehatan = :kesehatan,
        serapan_co = :serapan_co,
        produksi_o = :produksi_o,
        nama_jalan = :nama_jalan,
        kelurahan = :kelurahan,
        kecamatan = :kecamatan,
        koordinat_x = :koordinat_x,
        koordinat_y = :koordinat_y,
        keterangan = :keterangan
    WHERE id = :id";

    $stmt = $this->conn->prepare($sql);

    return $stmt->execute([
        ':nama_lokal'  => $nama_lokal,
        ':nama_latin'  => $nama_latin,
        ':family'      => $family,
        ':tahun_tanam' => $tahun_tanam,
        ':habitus'     => $habitus,
        ':status_kel'  => $status_kel,
        ':volume'      => $volume,
        ':kelas_awet'  => $kelas_awet,
        ':kelas_kuat'  => $kelas_kuat,
        ':berat_jenis' => $berat_jenis,
        ':kesehatan'   => $kesehatan,
        ':serapan_co'  => $serapan_co,
        ':produksi_o'  => $produksi_o,
        ':nama_jalan'  => $nama_jalan,
        ':kelurahan'   => $kelurahan,
        ':kecamatan'   => $kecamatan,
        ':koordinat_x' => $koordinat_x,
        ':koordinat_y' => $koordinat_y,
        ':keterangan'  => $keterangan,
        ':id'          => $id,
    ]);
}
    /**
     * Hapus data pohon berdasarkan ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM pohon WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Hitung jumlah pohon berdasarkan kondisi.
     * @param string $kondisi 'Sehat' | 'Kurang Baik' | 'MATI'
     */
    public function countByKondisi(string $kondisi): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM pohon WHERE kesehatan = ?"
        );
        $stmt->execute([$kondisi]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Hitung total seluruh pohon.
     */
    public function countAll(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM pohon")->fetchColumn();
    }
}
