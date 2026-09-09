<?php

require_once __DIR__ . '/NotificationModel.php';

/**
 * PengajuanModel
 * Kelas model untuk semua operasi CRUD pada tabel `pengajuan`.
 * Menggunakan PDO Prepared Statements untuk keamanan SQL Injection.
 *
 * Alur berjenjang (status_tahap) sesuai kebutuhan terbaru — langkah
 * "Divalidasi" oleh Validator sudah DIHAPUS dari alur. Alur sekarang:
 *   diajukan -> disurvey -> selesai (jika hasil survey "tidak_perlu")
 *   diajukan -> disurvey -> selesai (jika "perlu_pemangkasan", setelah
 *              Tim Tangkas mengunggah foto sesudah pemangkasan)
 * Role ID di bawah mengacu pada seed tetap di db/migration_rbac.sql
 * (role bawaan / is_system=1, tidak berubah lewat UI Manajemen Role).
 */
class PengajuanModel
{
    private const ROLE_PETUGAS_SURVEY = 4;
    private const ROLE_TIM_TANGKAS    = 8;
    private const ROLE_ADMIN          = 2;

    private PDO $conn;
    private NotificationModel $notif;

    public function __construct(PDO $db)
    {
        $this->conn  = $db;
        $this->notif = new NotificationModel($db);
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
        string $telepon,
        string $lokasi,
        string $dokumentasi = ''
    ): bool {
        $sql = "INSERT INTO pengajuan (No_Surat, Nama_Pemohon, Nomor_Telepon, Lokasi_Pohon, Disposisi_Surat, Dokumentasi)
                VALUES (:no_surat, :nama, :telepon, :lokasi, NOW(), :dokumentasi)";
        $stmt = $this->conn->prepare($sql);
        $ok = $stmt->execute([
            ':no_surat'    => $no_surat,
            ':nama'        => $nama,
            ':telepon'     => $telepon,
            ':lokasi'      => $lokasi,
            ':dokumentasi' => $dokumentasi,
        ]);

        if ($ok) {
            $newId = (int) $this->conn->lastInsertId();
            $this->notif->notifyRole(
                self::ROLE_PETUGAS_SURVEY,
                'Pengajuan Baru Perlu Disurvey',
                "Pengajuan \"{$no_surat}\" dari {$nama} di {$lokasi} menunggu survey lapangan.",
                'detail_pengajuan.php?id=' . $newId,
                $newId
            );
        }

        return $ok;
    }

    /**
     * Update data pengajuan secara lengkap.
     */
    public function update(
        int    $id,
        string $no_surat,
        string $nama,
        string $telepon,
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
                    Nomor_Telepon     = :telepon,
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
            ':telepon'          => $telepon,
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

    // ================================================================
    // ALUR BERJENJANG: survey -> validasi -> eksekusi (Tim Tangkas)
    // ================================================================

    /**
     * Petugas Survey mengisi hasil survey lapangan.
     * Jika hasil = 'tidak_perlu' -> alur langsung selesai.
     * Jika hasil = 'perlu_pemangkasan' -> notifikasi langsung ke Tim Tangkas
     * untuk eksekusi (langkah Validator sudah dihapus dari alur).
     */
    public function submitSurvey(int $id, int $petugasId, string $hasil, string $catatan): bool
    {
        $data = $this->getById($id);
        if (!$data) {
            return false;
        }

        $selesaiLangsung = ($hasil === 'tidak_perlu');

        $stmt = $this->conn->prepare(
            "UPDATE pengajuan SET
                petugas_survey_id = :petugas,
                hasil_survey      = :hasil,
                survey_tanggal    = NOW(),
                Survey_Pohon      = :catatan,
                status_tahap      = :status,
                Keterangan        = :keterangan
             WHERE Id = :id"
        );
        $ok = $stmt->execute([
            ':petugas'   => $petugasId,
            ':hasil'     => $hasil,
            ':catatan'   => $catatan,
            ':status'    => $selesaiLangsung ? 'selesai' : 'disurvey',
            ':keterangan'=> $selesaiLangsung ? 'Sudah' : 'Belum',
            ':id'        => $id,
        ]);

        if ($ok) {
            if ($selesaiLangsung) {
                $this->notif->notifyRole(
                    self::ROLE_ADMIN,
                    'Survey Selesai — Tidak Perlu Pemangkasan',
                    "Hasil survey pengajuan \"{$data['No_Surat']}\" menyatakan pohon tidak perlu ditangani.",
                    'detail_pengajuan.php?id=' . $id,
                    $id
                );
            } else {
                $this->notif->notifyRole(
                    self::ROLE_TIM_TANGKAS,
                    'Siap Dieksekusi di Lapangan',
                    "Pengajuan \"{$data['No_Surat']}\" hasil survey menyatakan perlu pemangkasan. Silakan unggah foto sesudah penanganan.",
                    'detail_pengajuan.php?id=' . $id,
                    $id
                );
            }
        }

        return $ok;
    }

    /**
     * Tim Tangkas mengunggah dokumentasi sesudah penanganan -> alur selesai.
     * Petugas Survey tidak berwenang menambah foto apapun; foto "sebelum"
     * sudah didapat dari foto wajib saat pengajuan dibuat oleh pemohon,
     * sehingga di tahap ini Tim Tangkas hanya mengunggah foto "sesudah".
     */
    public function eksekusi(int $id, int $timId, string $fotoSesudah): bool
    {
        $data = $this->getById($id);
        if (!$data) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE pengajuan SET
                tim_tangkas_id    = :tim,
                eksekusi_tanggal  = NOW(),
                Tanggal_Penanganan= CURDATE(),
                DokumentasiAfter  = COALESCE(NULLIF(:foto_sesudah, ''), DokumentasiAfter),
                status_tahap      = 'selesai',
                Keterangan        = 'Sudah'
             WHERE Id = :id"
        );
        $ok = $stmt->execute([
            ':tim'          => $timId,
            ':foto_sesudah' => $fotoSesudah,
            ':id'           => $id,
        ]);

        if ($ok) {
            $this->notif->notifyRole(
                self::ROLE_ADMIN,
                'Penanganan Selesai',
                "Pengajuan \"{$data['No_Surat']}\" telah selesai ditangani Tim Tangkas di lapangan.",
                'detail_pengajuan.php?id=' . $id,
                $id
            );
        }

        return $ok;
    }

    /**
     * Ambil data pengajuan dengan filter opsional: kata kunci, tanggal
     * (Disposisi_Surat), dan status (Keterangan Sudah/Belum).
     */
    public function filter(string $keyword = '', string $tanggal = '', string $status = ''): array
    {
        $sql    = "SELECT * FROM pengajuan WHERE 1=1";
        $params = [];

        if ($keyword !== '') {
            $sql .= " AND (No_Surat LIKE :keyword OR Nama_Pemohon LIKE :keyword2)";
            $params[':keyword']  = "%{$keyword}%";
            $params[':keyword2'] = "%{$keyword}%";
        }
        if ($tanggal !== '') {
            $sql .= " AND Disposisi_Surat = :tanggal";
            $params[':tanggal'] = $tanggal;
        }
        if ($status !== '' && in_array($status, ['Sudah', 'Belum'], true)) {
            $sql .= " AND Keterangan = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY Id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil daftar tanggal Disposisi_Surat unik, untuk dropdown filter
     * (mirip filter Excel: hanya menampilkan tanggal yang benar-benar ada).
     */
    public function getDistinctTanggal(): array
    {
        $stmt = $this->conn->query(
            "SELECT DISTINCT Disposisi_Surat FROM pengajuan WHERE Disposisi_Surat IS NOT NULL ORDER BY Disposisi_Surat DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}