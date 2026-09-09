<?php

/**
 * PohonImportHelper
 * ---------------------------------------------------------------
 * Helper untuk fitur "Import Data Pohon dari Excel" (poin 5a pada
 * dokumen skema kebutuhan). Mendukung 2 format file:
 *   - .csv  : dibaca langsung dengan fgetcsv (paling disarankan, dan
 *             inilah format template yang disediakan untuk didownload
 *             — bisa dibuka/diedit langsung dengan Excel).
 *   - .xlsx : dibaca dengan parser ringan bawaan (ZipArchive + XML)
 *             tanpa perlu library composer. Hanya membaca sheet
 *             pertama, nilai sel apa adanya (tanpa formula).
 *
 * Baris pertama file WAJIB berisi nama kolom (header), sesuai nama
 * field pada tabel `pohon` (lihat TEMPLATE_COLUMNS di bawah).
 * Urutan kolom bebas — dicocokkan berdasarkan nama header, bukan
 * posisi, supaya template lama/baru tetap kompatibel.
 */
class PohonImportHelper
{
    /**
     * Kolom yang dikenali importer, dalam urutan yang dipakai untuk
     * menyusun template download. Kolom bertanda (*) wajib diisi.
     */
    public const TEMPLATE_COLUMNS = [
        'nama_lokal'   => 'Nama Lokal (*)',
        'nama_latin'   => 'Nama Latin',
        'family'       => 'Family',
        'tahun_tanam'  => 'Tahun Tanam',
        'habitus'      => 'Habitus',
        'status_kel'   => 'Status Kepemilikan',
        'volume'       => 'Volume',
        'kelas_awet'   => 'Kelas Awet',
        'kelas_kuat'   => 'Kelas Kuat',
        'berat_jenis'  => 'Berat Jenis',
        'kesehatan'    => 'Kondisi Kesehatan (*) [Sehat/Kurang Sehat/Sakit]',
        'serapan_co'   => 'Serapan CO2',
        'produksi_o'   => 'Produksi O2',
        'nama_jalan'   => 'Nama Jalan (*)',
        'kelurahan'    => 'Kelurahan',
        'kecamatan'    => 'Kecamatan',
        'koordinat_x'  => 'Longitude / Koordinat X (*)',
        'koordinat_y'  => 'Latitude / Koordinat Y (*)',
        'keterangan'   => 'Keterangan',
        'umur_pohon'   => 'Umur Pohon',
    ];

    public const REQUIRED_COLUMNS = ['nama_lokal', 'kesehatan', 'nama_jalan', 'koordinat_x', 'koordinat_y'];

    /**
     * Baca file upload (csv atau xlsx) dan kembalikan array baris
     * asosiatif (key = nama kolom internal seperti di TEMPLATE_COLUMNS).
     * Baris yang seluruhnya kosong dilewati.
     *
     * @return array{header_ok: bool, rows: array<int,array<string,string>>, error: ?string}
     */
    public function readFile(string $tmpPath, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $raw = $this->parseCsv($tmpPath);
        } elseif ($ext === 'xlsx') {
            $raw = $this->parseXlsx($tmpPath);
        } else {
            return ['header_ok' => false, 'rows' => [], 'error' => 'Format file tidak didukung. Gunakan .csv atau .xlsx.'];
        }

        if ($raw === null || count($raw) === 0) {
            return ['header_ok' => false, 'rows' => [], 'error' => 'File kosong atau gagal dibaca.'];
        }

        // Baris pertama = header. Petakan nama header (case-insensitive,
        // fleksibel terhadap label panjang di TEMPLATE_COLUMNS maupun
        // nama kolom internal polos seperti "nama_lokal").
        $headerRow = array_shift($raw);
        $colMap    = []; // index kolom (0-based) => key internal

        foreach ($headerRow as $idx => $headerText) {
            $key = $this->matchColumnKey((string) $headerText);
            if ($key !== null) {
                $colMap[$idx] = $key;
            }
        }

        if (empty($colMap)) {
            return ['header_ok' => false, 'rows' => [], 'error' => 'Header kolom tidak dikenali. Gunakan template yang disediakan.'];
        }

        $rows = [];
        foreach ($raw as $line) {
            $assoc     = [];
            $hasValue  = false;
            foreach ($colMap as $idx => $key) {
                $val = trim((string) ($line[$idx] ?? ''));
                if ($val !== '') {
                    $hasValue = true;
                }
                $assoc[$key] = $val;
            }
            if ($hasValue) {
                $rows[] = $assoc;
            }
        }

        return ['header_ok' => true, 'rows' => $rows, 'error' => null];
    }

    /** Cocokkan teks header ke key kolom internal. */
    private function matchColumnKey(string $headerText): ?string
    {
        $norm = strtolower(trim($headerText));
        $norm = preg_replace('/\s*\(\*\)\s*/', '', $norm); // buang tanda wajib "(*)"
        $norm = preg_replace('/\[.*?\]/', '', $norm);       // buang catatan "[...]"
        $norm = trim($norm);

        foreach (self::TEMPLATE_COLUMNS as $key => $label) {
            $labelNorm = strtolower(preg_replace('/\s*\(\*\)\s*/', '', $label));
            $labelNorm = trim(preg_replace('/\[.*?\]/', '', $labelNorm));
            if ($norm === strtolower($key) || $norm === $labelNorm) {
                return $key;
            }
        }
        return null;
    }

    /** Validasi satu baris hasil import. Return string pesan error, atau null jika valid. */
    public function validateRow(array $row): ?string
    {
        foreach (self::REQUIRED_COLUMNS as $req) {
            if (trim($row[$req] ?? '') === '') {
                return "Kolom '{$req}' wajib diisi";
            }
        }
        if (!is_numeric(str_replace(',', '.', $row['koordinat_x']))) {
            return 'Koordinat X (longitude) harus berupa angka';
        }
        if (!is_numeric(str_replace(',', '.', $row['koordinat_y']))) {
            return 'Koordinat Y (latitude) harus berupa angka';
        }
        return null;
    }

    // ================================================================
    // ===== PARSER CSV =====
    // ================================================================
    private function parseCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return null;
        }
        // Lewati BOM UTF-8 jika ada, supaya header pertama tidak "rusak".
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            // Sebagian export Excel Indonesia memakai delimiter ";" —
            // deteksi otomatis dari baris pertama.
            if (count($data) === 1 && strpos($data[0], ';') !== false) {
                $data = str_getcsv($data[0], ';');
            }
            $rows[] = $data;
        }
        fclose($handle);
        return $rows;
    }

    // ================================================================
    // ===== PARSER XLSX (ringan, tanpa composer) =====
    // ================================================================
    private function parseXlsx(string $path): ?array
    {
        if (!class_exists('ZipArchive')) {
            return null;
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        // Shared strings (teks yang dipakai berulang disimpan terpisah oleh Excel)
        $shared = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $prevEntities = libxml_disable_entity_loader(true);
            $ss = @simplexml_load_string($ssXml);
            libxml_disable_entity_loader($prevEntities);
            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    if (isset($si->t)) {
                        $shared[] = (string) $si->t;
                    } else {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $shared[] = $text;
                    }
                }
            }
        }

        // Cari sheet pertama
        $sheetPath = null;
        for ($i = 1; $i <= 10; $i++) {
            if ($zip->locateName("xl/worksheets/sheet{$i}.xml") !== false) {
                $sheetPath = "xl/worksheets/sheet{$i}.xml";
                break;
            }
        }
        if ($sheetPath === null) {
            $zip->close();
            return null;
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();
        if ($sheetXml === false) {
            return null;
        }

        $prevEntities = libxml_disable_entity_loader(true);
        $xml = @simplexml_load_string($sheetXml);
        libxml_disable_entity_loader($prevEntities);
        if ($xml === false || !isset($xml->sheetData)) {
            return null;
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $maxCol  = -1;
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                if (!preg_match('/([A-Z]+)(\d+)/', $ref, $m)) {
                    continue;
                }
                $colIndex = $this->colLettersToIndex($m[1]);
                $type     = (string) $c['t'];

                if ($type === 's') {
                    $idx = (int) $c->v;
                    $value = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($c->is->t) ? (string) $c->is->t : '';
                } else {
                    $value = (string) $c->v;
                }

                $rowData[$colIndex] = $value;
                $maxCol = max($maxCol, $colIndex);
            }
            // Ratakan jadi array 0..maxCol supaya index kolom konsisten antar baris.
            $flat = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $flat[] = $rowData[$i] ?? '';
            }
            $rows[] = $flat;
        }

        return $rows;
    }

    private function colLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $result  = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $result = $result * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $result - 1;
    }
}