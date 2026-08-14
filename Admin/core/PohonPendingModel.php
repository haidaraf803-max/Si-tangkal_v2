<?php

/**
 * PohonPendingModel
 * ---------------------------------------------------------
 * Model untuk tabel staging `pohon_pending`.
 *
 * Alur:
 *  1. User "Tambah Pohon" -> data masuk ke pohon_pending (status='pending'),
 *     BUKAN langsung ke tabel `pohon`.
 *  2. Data pending dicek validitasnya (lihat validateRow()).
 *  3. Admin (atau proses otomatis) menjalankan approve($id):
 *       - jika valid -> INSERT ke tabel `pohon` (lewat PohonModel::create),
 *         baris pending ditandai status='valid' & pohon_id diisi.
 *       - jika ternyata tidak valid -> ditolak otomatis, status='invalid'.
 *     Atau admin menjalankan reject($id, $catatan) untuk menolak manual.
 *  4. Data pending (termasuk yang sudah divalidasi) bisa diexport CSV
 *     lewat Admin/export.php?modul=pohon_pending (lihat getForExport()).
 * ---------------------------------------------------------
 */
class PohonPendingModel
{
    private PDO $conn;

    /** Kondisi kesehatan yang valid, sesuai kolom ENUM tabel pohon. */
    private array $allowedKesehatan = ['Sehat', 'Kurang Sehat', 'Sakit'];

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ================================================================
    // ===== BACA DATA =====
    // ================================================================

    /** Ambil semua data pending (opsional filter status), terbaru dulu. */
    public function getAll(?string $status = null): array
    {
        if ($status !== null && $status !== '') {
            $stmt = $this->conn->prepare(
                "SELECT p.*, u.Name AS dibuat_oleh_nama, v.Name AS divalidasi_oleh_nama
                 FROM pohon_pending p
                 LEFT JOIN t_users u ON u.UserId = p.dibuat_oleh
                 LEFT JOIN t_users v ON v.UserId = p.divalidasi_oleh
                 WHERE p.status = ?
                 ORDER BY p.id DESC"
            );
            $stmt->execute([$status]);
        } else {
            $stmt = $this->conn->query(
                "SELECT p.*, u.Name AS dibuat_oleh_nama, v.Name AS divalidasi_oleh_nama
                 FROM pohon_pending p
                 LEFT JOIN t_users u ON u.UserId = p.dibuat_oleh
                 LEFT JOIN t_users v ON v.UserId = p.divalidasi_oleh
                 ORDER BY p.id DESC"
            );
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM pohon_pending WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM pohon_pending WHERE status = ?");
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    /** Data untuk export CSV (dipakai Admin/export.php lewat query generik,
     *  method ini disediakan untuk pemakaian langsung / API). */
    public function getForExport(): array
    {
        return $this->getAll();
    }

    // ================================================================
    // ===== SIMPAN KE STAGING (BUKAN ke tabel pohon) =====
    // ================================================================

    /**
     * Simpan pengajuan pohon baru ke staging. Data BELUM masuk ke tabel
     * `pohon` sampai divalidasi lewat approve().
     */
    public function create(array $data, ?int $userId = null): int|false
    {
        $sql = "INSERT INTO pohon_pending (
            no_pohon, nama_lokal, nama_latin, family, tahun_tanam, habitus,
            status_kel, volume, kelas_awet, kelas_kuat, berat_jenis, kesehatan,
            serapan_co, produksi_o, nama_jalan, kelurahan, kecamatan,
            koordinat_x, koordinat_y, keterangan, umur_pohon, foto,
            status, dibuat_oleh
        ) VALUES (
            :no_pohon, :nama_lokal, :nama_latin, :family, :tahun_tanam, :habitus,
            :status_kel, :volume, :kelas_awet, :kelas_kuat, :berat_jenis, :kesehatan,
            :serapan_co, :produksi_o, :nama_jalan, :kelurahan, :kecamatan,
            :koordinat_x, :koordinat_y, :keterangan, :umur_pohon, :foto,
            'pending', :dibuat_oleh
        )";

        $stmt = $this->conn->prepare($sql);

        $ok = $stmt->execute([
            ':no_pohon'    => $data['no_pohon'] ?? null,
            ':nama_lokal'  => trim((string) ($data['nama_lokal'] ?? '')),
            ':nama_latin'  => trim((string) ($data['nama_latin'] ?? '')),
            ':family'      => trim((string) ($data['family'] ?? '')),
            ':tahun_tanam' => trim((string) ($data['tahun_tanam'] ?? '')),
            ':habitus'     => trim((string) ($data['habitus'] ?? '')),
            ':status_kel'  => trim((string) ($data['status_kel'] ?? '')),
            ':volume'      => is_numeric($data['volume'] ?? null) ? (float) $data['volume'] : 0,
            ':kelas_awet'  => trim((string) ($data['kelas_awet'] ?? '')),
            ':kelas_kuat'  => trim((string) ($data['kelas_kuat'] ?? '')),
            ':berat_jenis' => is_numeric($data['berat_jenis'] ?? null) ? (float) $data['berat_jenis'] : 0,
            ':kesehatan'   => trim((string) ($data['kesehatan'] ?? '')),
            ':serapan_co'  => is_numeric($data['serapan_co'] ?? null) ? (float) $data['serapan_co'] : 0,
            ':produksi_o'  => is_numeric($data['produksi_o'] ?? null) ? (float) $data['produksi_o'] : null,
            ':nama_jalan'  => trim((string) ($data['nama_jalan'] ?? '')),
            ':kelurahan'   => trim((string) ($data['kelurahan'] ?? '')),
            ':kecamatan'   => trim((string) ($data['kecamatan'] ?? '')),
            ':koordinat_x' => is_numeric($data['koordinat_x'] ?? null) ? (float) $data['koordinat_x'] : 0,
            ':koordinat_y' => is_numeric($data['koordinat_y'] ?? null) ? (float) $data['koordinat_y'] : null,
            ':keterangan'  => trim((string) ($data['keterangan'] ?? '')),
            ':umur_pohon'  => trim((string) ($data['umur_pohon'] ?? '')),
            ':foto'        => $data['foto'] ?? null,
            ':dibuat_oleh' => $userId,
        ]);

        return $ok ? (int) $this->conn->lastInsertId() : false;
    }

    // ================================================================
    // ===== VALIDASI =====
    // ================================================================

    /**
     * Cek apakah satu baris pending valid untuk dipindahkan ke tabel pohon.
     * Aturan mengikuti field wajib yang sama seperti form Tambah Pohon
     * (Admin/pohon.php): nama_lokal, kesehatan, nama_jalan, koordinat.
     *
     * @return array{ok: bool, errors: string[]}
     */
    public function validateRow(array $row): array
    {
        $errors = [];

        if (trim((string) ($row['nama_lokal'] ?? '')) === '') {
            $errors[] = 'Nama lokal wajib diisi.';
        }

        $kesehatan = trim((string) ($row['kesehatan'] ?? ''));
        if ($kesehatan === '') {
            $errors[] = 'Kondisi kesehatan wajib diisi.';
        } elseif (!in_array($kesehatan, $this->allowedKesehatan, true)) {
            $errors[] = "Kondisi kesehatan '{$kesehatan}' tidak valid (harus Sehat/Kurang Sehat/Sakit).";
        }

        if (trim((string) ($row['nama_jalan'] ?? '')) === '') {
            $errors[] = 'Nama jalan wajib diisi.';
        }

        $x = $row['koordinat_x'] ?? null; // longitude
        $y = $row['koordinat_y'] ?? null; // latitude
        if ($x === null || $x === '' || !is_numeric($x)) {
            $errors[] = 'Longitude tidak valid.';
        } elseif ((float) $x < 94 || (float) $x > 142) {
            $errors[] = 'Longitude di luar rentang wilayah Indonesia.';
        }
        if ($y === null || $y === '' || !is_numeric($y)) {
            $errors[] = 'Latitude tidak valid.';
        } elseif ((float) $y < -11 || (float) $y > 6) {
            $errors[] = 'Latitude di luar rentang wilayah Indonesia.';
        }

        foreach (['volume', 'berat_jenis', 'serapan_co', 'produksi_o'] as $numField) {
            $val = $row[$numField] ?? null;
            if ($val !== null && $val !== '' && !is_numeric($val)) {
                $errors[] = ucfirst(str_replace('_', ' ', $numField)) . ' harus berupa angka.';
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    // ================================================================
    // ===== VALIDASI & PINDAHKAN KE TABEL pohon =====
    // ================================================================

    /**
     * Validasi baris pending lalu, jika valid, pindahkan ke tabel `pohon`
     * lewat PohonModel::create(). Jika tidak valid, baris otomatis
     * ditandai invalid berikut alasannya (tidak jadi dipindahkan).
     *
     * @return array{success: bool, message: string, pohon_id?: int}
     */
    public function approve(int $id, PohonModel $pohonModel, ?int $userId = null): array
    {
        $row = $this->getById($id);
        if (!$row) {
            return ['success' => false, 'message' => 'Data pending tidak ditemukan.'];
        }
        if ($row['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Data ini sudah pernah divalidasi sebelumnya.'];
        }

        $check = $this->validateRow($row);

        if (!$check['ok']) {
            $this->markInvalid($id, implode(' ', $check['errors']), $userId);
            return [
                'success' => false,
                'message' => 'Data tidak valid: ' . implode(' ', $check['errors']),
            ];
        }

        $newId = $pohonModel->create(
            (string) $row['nama_lokal'],
            (string) $row['nama_latin'],
            (string) $row['family'],
            (string) $row['tahun_tanam'],
            (string) $row['habitus'],
            (string) $row['status_kel'],
            (float) $row['volume'],
            (string) $row['kelas_awet'],
            (string) $row['kelas_kuat'],
            (float) $row['berat_jenis'],
            (string) $row['kesehatan'],
            (float) $row['serapan_co'],
            (float) $row['produksi_o'],
            (string) $row['nama_jalan'],
            (string) $row['kelurahan'],
            (string) $row['kecamatan'],
            (float) $row['koordinat_x'],
            (float) $row['koordinat_y'],
            (string) $row['keterangan'],
            (string) $row['umur_pohon'],
            $row['foto'] ?: null
        );

        if (!$newId) {
            return ['success' => false, 'message' => 'Gagal menyimpan ke tabel pohon.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE pohon_pending
             SET status = 'valid', pohon_id = :pohon_id,
                 catatan_validasi = NULL,
                 divalidasi_oleh = :uid, divalidasi_pada = NOW()
             WHERE id = :id"
        );
        $stmt->execute([':pohon_id' => $newId, ':uid' => $userId, ':id' => $id]);

        return ['success' => true, 'message' => 'Data valid & berhasil dipindahkan ke data pohon.', 'pohon_id' => $newId];
    }

    /** Tandai baris pending sebagai invalid (dipakai internal approve() & reject manual). */
    private function markInvalid(int $id, string $catatan, ?int $userId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE pohon_pending
             SET status = 'invalid', catatan_validasi = :catatan,
                 divalidasi_oleh = :uid, divalidasi_pada = NOW()
             WHERE id = :id"
        );
        $stmt->execute([':catatan' => $catatan, ':uid' => $userId, ':id' => $id]);
    }

    /** Tolak manual oleh admin (terlepas dari hasil validasi otomatis). */
    public function reject(int $id, string $catatan, ?int $userId = null): bool
    {
        $row = $this->getById($id);
        if (!$row || $row['status'] !== 'pending') {
            return false;
        }
        $this->markInvalid($id, $catatan !== '' ? $catatan : 'Ditolak oleh admin.', $userId);
        return true;
    }

    // ================================================================
    // ===== HAPUS =====
    // ================================================================

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM pohon_pending WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
