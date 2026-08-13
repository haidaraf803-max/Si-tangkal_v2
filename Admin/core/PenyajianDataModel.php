<?php

/**
 * PenyajianDataModel.php
 * ---------------------------------------------------------
 * Sesuai dokumen kebutuhan menu "Penyajian Data":
 *   - Nilai IKTL setiap tahun: diinput manual, tabel/diagram
 *   - Persentase RTH setiap tahun: diinput manual
 * Disimpan dalam 1 tabel `penyajian_data` dengan kolom `jenis`
 * ('iktl' | 'rth_persen') supaya mudah dipakai bersama di 1
 * halaman & 1 chart, tapi tetap dipisah query-nya per jenis.
 * ---------------------------------------------------------
 */
class PenyajianDataModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getByJenis(string $jenis): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM penyajian_data WHERE jenis = ? ORDER BY tahun ASC"
        );
        $stmt->execute([$jenis]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upsert(string $jenis, int $tahun, float $nilai, ?string $keterangan, ?int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO penyajian_data (jenis, tahun, nilai, keterangan, dibuat_oleh)
             VALUES (:jenis, :tahun, :nilai, :ket, :user)
             ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), keterangan = VALUES(keterangan)"
        );
        return $stmt->execute([
            ':jenis' => $jenis, ':tahun' => $tahun, ':nilai' => $nilai,
            ':ket'   => $keterangan, ':user' => $userId,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM penyajian_data WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM penyajian_data WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
