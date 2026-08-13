<?php
/**
 * core/CsvExportHelper.php
 * ---------------------------------------------------------
 * Helper kecil untuk menyeragamkan cara semua halaman Admin
 * mengekspor data ke CSV (baik "Semua Data" maupun "Per Tanggal").
 * ---------------------------------------------------------
 */

class CsvExportHelper
{
    /**
     * Kirim data sebagai file CSV ke browser lalu hentikan eksekusi.
     *
     * @param string   $filename Nama file yang diunduh (tanpa path)
     * @param string[] $headers  Baris judul kolom
     * @param array    $rows     Baris data, tiap baris array nilai kolom
     */
    public static function stream(string $filename, array $headers, array $rows): void
    {
        // Buang semua output buffer yang mungkin sudah berjalan supaya
        // file CSV tidak tercampur HTML/whitespace lain.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // BOM supaya karakter dibuka dengan benar di Excel
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }

    /**
     * Susun nama file berdasarkan modul & mode filter tanggal.
     */
    public static function buildFilename(string $modul, string $mode, string $tanggal = '', string $dari = '', string $sampai = ''): string
    {
        $ts = date('Ymd_His');
        if ($mode === 'tanggal' && $tanggal !== '') {
            return $modul . '_' . $tanggal . '_' . $ts . '.csv';
        }
        if ($mode === 'rentang' && $dari !== '' && $sampai !== '') {
            return $modul . '_' . $dari . '_sd_' . $sampai . '_' . $ts . '.csv';
        }
        return $modul . '_semua_' . $ts . '.csv';
    }

    /**
     * Validasi format tanggal YYYY-MM-DD. Mengembalikan true/false.
     */
    public static function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
}
