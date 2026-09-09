<?php

/**
 * DokumenModel
 * Model untuk menu "Dokumen" (skema terbaru 20 Agustus 2026, poin 6a):
 * daftar nama dokumen + tombol unduh. Admin bisa unggah/hapus dokumen,
 * semua role lain hanya bisa melihat & mengunduh (lihat RBAC di
 * db/migration_dokumen.sql).
 */
class DokumenModel
{
    private PDO $conn;

    /** Folder fisik penyimpanan file dokumen (relatif thd root aplikasi, sejalan dengan assets/foto untuk foto). */
    private string $uploadDir = __DIR__ . '/../../assets/dokumen/';

    /** Ekstensi file yang diizinkan diupload. */
    private array $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png'];

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT d.*, u.Name AS diunggah_oleh_nama
             FROM dokumen d
             LEFT JOIN t_users u ON u.UserId = d.diunggah_oleh
             ORDER BY d.dibuat_pada DESC, d.id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM dokumen WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function dir(): string
    {
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        return $this->uploadDir;
    }

    /**
     * Upload file dokumen dari <input type="file" name="file_dokumen">.
     * Mengembalikan array ['path' => ..., 'asli' => ..., 'ukuran' => ...]
     * atau null jika tidak ada file / gagal / ekstensi tidak diizinkan.
     */
    public function uploadFile(?array $file): ?array
    {
        if (empty($file) || empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExt, true)) {
            return null;
        }

        $safeName = 'dok_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest     = $this->dir() . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return [
            'path'   => 'assets/dokumen/' . $safeName,
            'asli'   => basename($file['name']),
            'ukuran' => (int) ($file['size'] ?? filesize($dest)),
        ];
    }

    public function create(string $namaDokumen, ?string $keterangan, array $uploaded, ?int $userId): int|false
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO dokumen (nama_dokumen, keterangan, file_path, file_asli, ukuran_bytes, diunggah_oleh)
             VALUES (:nama, :ket, :path, :asli, :ukuran, :user)"
        );
        $ok = $stmt->execute([
            ':nama'   => $namaDokumen,
            ':ket'    => $keterangan ?: null,
            ':path'   => $uploaded['path'],
            ':asli'   => $uploaded['asli'],
            ':ukuran' => $uploaded['ukuran'],
            ':user'   => $userId,
        ]);
        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    /** Hapus data dokumen + file fisiknya. */
    public function delete(int $id): bool
    {
        $data = $this->getById($id);
        if ($data && !empty($data['file_path'])) {
            $full = __DIR__ . '/../../' . $data['file_path'];
            if (is_file($full)) {
                @unlink($full);
            }
        }
        $stmt = $this->conn->prepare("DELETE FROM dokumen WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
