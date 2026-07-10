<?php

/**
 * MonitoringModel
 * Model CRUD untuk tabel `monitoring` & `monitoring_media`.
 * Mendukung upload banyak file (foto/video) sekaligus, mengikuti
 * konvensi yang sama seperti api/monitoring/*.php.
 */
class MonitoringModel
{
    private PDO $conn;

    private array $allowedImages = ['jpg', 'jpeg', 'png', 'webp'];
    private array $allowedVideos = ['mp4', 'mov', 'avi', 'mkv'];

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Ambil semua data monitoring beserta info pohon terkait & jumlah media.
     */
    public function getAll(string $keyword = '', string $statusFilter = ''): array
    {
        $sql = "SELECT m.*,
                       p.nama_lokal, p.nama_latin, p.nama_jalan, p.kelurahan, p.kecamatan,
                       u.Name AS petugas_name, u.Username AS petugas_username,
                       (SELECT COUNT(*) FROM monitoring_media mm WHERE mm.monitoring_id = m.id) AS media_count
                FROM monitoring m
                LEFT JOIN pohon p ON p.id = m.pohon_id
                LEFT JOIN t_users u ON u.UserId = m.user_id";

        $conditions = [];
        $params = [];

        if ($keyword !== '') {
            $conditions[] = "(p.nama_lokal LIKE :kw1 OR p.nama_jalan LIKE :kw2 OR m.kesehatan_monitoring LIKE :kw3 OR m.catatan LIKE :kw4)";
            $likeKeyword = "%{$keyword}%";
            $params[':kw1'] = $likeKeyword;
            $params[':kw2'] = $likeKeyword;
            $params[':kw3'] = $likeKeyword;
            $params[':kw4'] = $likeKeyword;
        }

        if ($statusFilter !== '') {
            $conditions[] = "m.status_tindak_lanjut = :status";
            $params[':status'] = $statusFilter;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY m.tanggal_monitoring DESC, m.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil satu data monitoring + data pohon terkait + seluruh medianya.
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT m.*,
                    p.nama_lokal, p.nama_latin, p.family, p.nama_jalan, p.kelurahan, p.kecamatan,
                    p.kesehatan AS kesehatan_pohon_saat_ini, p.foto AS foto_pohon,
                    u.Name AS petugas_name, u.Username AS petugas_username
             FROM monitoring m
             LEFT JOIN pohon p ON p.id = m.pohon_id
             LEFT JOIN t_users u ON u.UserId = m.user_id
             WHERE m.id = ?"
        );
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return false;
        }

        $data['media'] = $this->getMedia($id);

        return $data;
    }

    /**
     * Ambil seluruh media untuk satu monitoring_id.
     */
    public function getMedia(int $monitoringId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM monitoring_media WHERE monitoring_id = ? ORDER BY created_at ASC, id ASC"
        );
        $stmt->execute([$monitoringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Daftar pohon untuk dropdown pilihan pada form monitoring.
     */
    public function getPohonOptions(): array
    {
        $stmt = $this->conn->query(
            "SELECT id, nama_lokal, nama_jalan, kesehatan FROM pohon ORDER BY nama_lokal ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tambah data monitoring baru + upload media (boleh lebih dari satu file).
     *
     * @param array $files Struktur asli dari $_FILES['files'] (boleh single atau multiple)
     */
    public function create(
        int $pohon_id,
        int $user_id,
        string $tanggal_monitoring,
        string $kesehatan_monitoring,
        string $catatan,
        array $files = [],
        array $detail = []
    ): array {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "INSERT INTO monitoring
                    (pohon_id, user_id, tanggal_monitoring, kesehatan_monitoring, catatan,
                     tinggi_pohon, diameter_batang, lebar_tajuk, jenis_gangguan,
                     tingkat_keparahan, rekomendasi_tindakan, status_tindak_lanjut,
                     latitude, longitude)
                 VALUES
                    (:pohon_id, :user_id, :tanggal_monitoring, :kesehatan_monitoring, :catatan,
                     :tinggi_pohon, :diameter_batang, :lebar_tajuk, :jenis_gangguan,
                     :tingkat_keparahan, :rekomendasi_tindakan, :status_tindak_lanjut,
                     :latitude, :longitude)"
            );
            $stmt->execute([
                ':pohon_id'             => $pohon_id,
                ':user_id'              => $user_id,
                ':tanggal_monitoring'   => $tanggal_monitoring ?: date('Y-m-d'),
                ':kesehatan_monitoring' => $kesehatan_monitoring ?: null,
                ':catatan'              => $catatan ?: null,
                ':tinggi_pohon'         => ($detail['tinggi_pohon'] ?? '') !== '' ? (float) $detail['tinggi_pohon'] : null,
                ':diameter_batang'      => ($detail['diameter_batang'] ?? '') !== '' ? (float) $detail['diameter_batang'] : null,
                ':lebar_tajuk'          => ($detail['lebar_tajuk'] ?? '') !== '' ? (float) $detail['lebar_tajuk'] : null,
                ':jenis_gangguan'       => $detail['jenis_gangguan'] ?? null,
                ':tingkat_keparahan'    => $detail['tingkat_keparahan'] ?? null,
                ':rekomendasi_tindakan' => $detail['rekomendasi_tindakan'] ?? null,
                ':status_tindak_lanjut' => $detail['status_tindak_lanjut'] ?? 'Belum',
                ':latitude'             => ($detail['latitude'] ?? '') !== '' ? (float) $detail['latitude'] : null,
                ':longitude'            => ($detail['longitude'] ?? '') !== '' ? (float) $detail['longitude'] : null,
            ]);

            $monitoringId = (int) $this->conn->lastInsertId();

            $this->uploadMedia($monitoringId, $files);

            $this->conn->commit();

            return ['success' => true, 'id' => $monitoringId, 'message' => 'Monitoring berhasil ditambahkan.'];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => 'Gagal menambahkan monitoring: ' . $e->getMessage()];
        }
    }

    /**
     * Update data monitoring. File baru (jika ada) akan DITAMBAHKAN (bukan
     * mengganti seluruh media lama). Media lama tertentu bisa dihapus lewat
     * $deleteMediaIds.
     */
    public function update(
        int $id,
        int $pohon_id,
        string $tanggal_monitoring,
        string $kesehatan_monitoring,
        string $catatan,
        array $files = [],
        array $deleteMediaIds = [],
        array $detail = []
    ): array {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "UPDATE monitoring
                 SET pohon_id = :pohon_id,
                     tanggal_monitoring = :tanggal_monitoring,
                     kesehatan_monitoring = :kesehatan_monitoring,
                     catatan = :catatan,
                     tinggi_pohon = :tinggi_pohon,
                     diameter_batang = :diameter_batang,
                     lebar_tajuk = :lebar_tajuk,
                     jenis_gangguan = :jenis_gangguan,
                     tingkat_keparahan = :tingkat_keparahan,
                     rekomendasi_tindakan = :rekomendasi_tindakan,
                     status_tindak_lanjut = :status_tindak_lanjut,
                     latitude = :latitude,
                     longitude = :longitude
                 WHERE id = :id"
            );
            $stmt->execute([
                ':pohon_id'             => $pohon_id,
                ':tanggal_monitoring'   => $tanggal_monitoring ?: date('Y-m-d'),
                ':kesehatan_monitoring' => $kesehatan_monitoring ?: null,
                ':catatan'              => $catatan ?: null,
                ':tinggi_pohon'         => ($detail['tinggi_pohon'] ?? '') !== '' ? (float) $detail['tinggi_pohon'] : null,
                ':diameter_batang'      => ($detail['diameter_batang'] ?? '') !== '' ? (float) $detail['diameter_batang'] : null,
                ':lebar_tajuk'          => ($detail['lebar_tajuk'] ?? '') !== '' ? (float) $detail['lebar_tajuk'] : null,
                ':jenis_gangguan'       => $detail['jenis_gangguan'] ?? null,
                ':tingkat_keparahan'    => $detail['tingkat_keparahan'] ?? null,
                ':rekomendasi_tindakan' => $detail['rekomendasi_tindakan'] ?? null,
                ':status_tindak_lanjut' => $detail['status_tindak_lanjut'] ?? 'Belum',
                ':latitude'             => ($detail['latitude'] ?? '') !== '' ? (float) $detail['latitude'] : null,
                ':longitude'            => ($detail['longitude'] ?? '') !== '' ? (float) $detail['longitude'] : null,
                ':id'                   => $id,
            ]);

            if (!empty($deleteMediaIds)) {
                $this->deleteMediaItems($id, $deleteMediaIds);
            }

            $this->uploadMedia($id, $files);

            $this->conn->commit();

            return ['success' => true, 'message' => 'Monitoring berhasil diperbarui.'];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => 'Gagal memperbarui monitoring: ' . $e->getMessage()];
        }
    }

    /**
     * Hapus item media tertentu milik satu monitoring (file fisik + baris DB).
     */
    public function deleteMediaItems(int $monitoringId, array $mediaIds): void
    {
        $mediaIds = array_filter(array_map('intval', $mediaIds));
        if (empty($mediaIds)) {
            return;
        }

        $in = implode(',', array_fill(0, count($mediaIds), '?'));
        $stmt = $this->conn->prepare(
            "SELECT * FROM monitoring_media WHERE monitoring_id = ? AND id IN ($in)"
        );
        $stmt->execute(array_merge([$monitoringId], $mediaIds));
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $fullPath = BASE_PATH . '/' . $item['file_path'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        $stmtDel = $this->conn->prepare(
            "DELETE FROM monitoring_media WHERE monitoring_id = ? AND id IN ($in)"
        );
        $stmtDel->execute(array_merge([$monitoringId], $mediaIds));
    }

    /**
     * Hapus data monitoring beserta seluruh media (file fisik + baris DB).
     */
    public function delete(int $id): array
    {
        try {
            $this->conn->beginTransaction();

            $media = $this->getMedia($id);
            foreach ($media as $item) {
                $fullPath = BASE_PATH . '/' . $item['file_path'];
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $this->conn->prepare("DELETE FROM monitoring_media WHERE monitoring_id = ?")->execute([$id]);
            $this->conn->prepare("DELETE FROM monitoring WHERE id = ?")->execute([$id]);

            $this->conn->commit();

            return ['success' => true, 'message' => 'Monitoring berhasil dihapus.'];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => 'Gagal menghapus monitoring: ' . $e->getMessage()];
        }
    }

    public function countAll(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM monitoring")->fetchColumn();
    }

    public function countByKesehatan(string $kesehatan): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM monitoring WHERE kesehatan_monitoring = ?");
        $stmt->execute([$kesehatan]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Upload & simpan banyak file (foto/video) untuk satu monitoring_id.
     * Menerima struktur asli $_FILES['files'] (single ataupun multiple).
     */
    private function uploadMedia(int $monitoringId, array $files): void
    {
        if (empty($files) || empty($files['name'])) {
            return;
        }

        // Normalisasi jika upload hanya 1 file (tmp_name berupa string, bukan array)
        if (!is_array($files['tmp_name'])) {
            $files = [
                'name'     => [$files['name']],
                'type'     => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error'    => [$files['error']],
                'size'     => [$files['size'] ?? 0],
            ];
        }

        $todayFolder = date('Y-m-d');
        $fotoDir  = BASE_PATH . "/media/monitoring/$todayFolder/foto/";
        $videoDir = BASE_PATH . "/media/monitoring/$todayFolder/video/";

        if (!file_exists($fotoDir)) {
            mkdir($fotoDir, 0777, true);
        }
        if (!file_exists($videoDir)) {
            mkdir($videoDir, 0777, true);
        }

        $fotoCounter  = 1;
        $videoCounter = 1;
        $allowedExtensions = array_merge($this->allowedImages, $this->allowedVideos);

        foreach ($files['tmp_name'] as $key => $tmpName) {

            if (empty($tmpName) || $files['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = $files['name'][$key];
            $mimeType     = $files['type'][$key];
            $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $isVideo = str_contains($mimeType, 'video') || in_array($extension, $this->allowedVideos, true);

            if ($isVideo) {
                $mediaType = 'video';
                $newName   = date('Ymd_His') . '_' . uniqid() . '_' . $videoCounter . '.' . $extension;
                $savePath  = $videoDir . $newName;
                $dbPath    = "media/monitoring/$todayFolder/video/$newName";
                $videoCounter++;
            } else {
                $mediaType = 'foto';
                $newName   = date('Ymd_His') . '_' . uniqid() . '_' . $fotoCounter . '.' . $extension;
                $savePath  = $fotoDir . $newName;
                $dbPath    = "media/monitoring/$todayFolder/foto/$newName";
                $fotoCounter++;
            }

            if (move_uploaded_file($tmpName, $savePath)) {
                $stmtMedia = $this->conn->prepare(
                    "INSERT INTO monitoring_media (monitoring_id, media_type, file_name, file_path)
                     VALUES (:monitoring_id, :media_type, :file_name, :file_path)"
                );
                $stmtMedia->execute([
                    ':monitoring_id' => $monitoringId,
                    ':media_type'    => $mediaType,
                    ':file_name'     => $originalName,
                    ':file_path'     => $dbPath,
                ]);
            }
        }
    }
}