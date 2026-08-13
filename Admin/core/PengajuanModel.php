<?php

require_once __DIR__ . '/NotificationModel.php';

/**
 * PengajuanModel
 * Kelas model untuk semua operasi CRUD pada tabel `pengajuan`.
 * Menggunakan PDO Prepared Statements untuk keamanan SQL Injection.
 *
 * Sejak migration_advanced.sql, tabel ini juga menyimpan status
 * alur berjenjang (status_tahap) sesuai dokumen kebutuhan:
 *   diajukan -> disurvey -> divalidasi -> ditangani -> selesai
 * Role ID di bawah mengacu pada seed tetap di db/migration_rbac.sql
 * (role bawaan / is_system=1, tidak berubah lewat UI Manajemen Role).
 */
class PengajuanModel
{
    private const ROLE_VALIDATOR      = 3;
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
     * Jika hasil = 'tidak_perlu' -> alur langsung selesai (tidak perlu ke validator).
     * Jika hasil = 'perlu_pemangkasan' -> notifikasi ke Validator untuk approval.
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
                    self::ROLE_VALIDATOR,
                    'Hasil Survey Menunggu Validasi',
                    "Pengajuan \"{$data['No_Surat']}\" hasil survey menyatakan perlu pemangkasan, menunggu validasi Anda.",
                    'detail_pengajuan.php?id=' . $id,
                    $id
                );
            }
        }

        return $ok;
    }

    /**
     * Validator menyetujui atau menolak hasil survey.
     * Disetujui -> notifikasi ke Tim Tangkas untuk eksekusi.
     * Ditolak   -> kembali ke tahap 'diajukan' supaya disurvey ulang.
     */
    public function validate(int $id, int $validatorId, bool $approve, string $catatan): bool
    {
        $data = $this->getById($id);
        if (!$data) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE pengajuan SET
                validator_id      = :validator,
                validasi_catatan  = :catatan,
                validasi_tanggal  = NOW(),
                status_tahap      = :status
             WHERE Id = :id"
        );
        $ok = $stmt->execute([
            ':validator' => $validatorId,
            ':catatan'   => $catatan,
            ':status'    => $approve ? 'divalidasi' : 'diajukan',
            ':id'        => $id,
        ]);

        if ($ok) {
            if ($approve) {
                $this->notif->notifyRole(
                    self::ROLE_TIM_TANGKAS,
                    'Siap Dieksekusi di Lapangan',
                    "Pengajuan \"{$data['No_Surat']}\" telah divalidasi, silakan dokumentasikan sebelum & sesudah penanganan.",
                    'detail_pengajuan.php?id=' . $id,
                    $id
                );
            } else {
                $this->notif->notifyRole(
                    self::ROLE_PETUGAS_SURVEY,
                    'Hasil Survey Ditolak Validator',
                    "Pengajuan \"{$data['No_Surat']}\" perlu disurvey ulang. Catatan validator: {$catatan}",
                    'detail_pengajuan.php?id=' . $id,
                    $id
                );
            }
        }

        return $ok;
    }

    /**
     * Tim Tangkas mengunggah dokumentasi sebelum/sesudah -> alur selesai.
     */
    public function eksekusi(int $id, int $timId, string $fotoSebelum, string $fotoSesudah): bool
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
                Dokumentasi       = COALESCE(NULLIF(:foto_sebelum, ''), Dokumentasi),
                DokumentasiAfter  = COALESCE(NULLIF(:foto_sesudah, ''), DokumentasiAfter),
                status_tahap      = 'selesai',
                Keterangan        = 'Sudah'
             WHERE Id = :id"
        );
        $ok = $stmt->execute([
            ':tim'          => $timId,
            ':foto_sebelum' => $fotoSebelum,
            ':foto_sesudah' => $fotoSesudah,
            ':id'           => $id,
        ]);

        if ($ok) {
            $this->notif->notifyRole(
                self::ROLE_VALIDATOR,
                'Penanganan Selesai',
                "Pengajuan \"{$data['No_Surat']}\" telah selesai ditangani Tim Tangkas di lapangan.",
                'detail_pengajuan.php?id=' . $id,
                $id
            );
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
}