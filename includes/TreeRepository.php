<?php

require_once __DIR__ . '/config.php';

class TreeRepository
{
    private static ?array $cache = null;

    private static function loadAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (DATA_MODE === 'mysql') {
            self::$cache = self::loadAllFromMysql();
        } else {
            $json = file_get_contents(DATA_PATH . '/trees.json');
            self::$cache = json_decode($json, true) ?: [];
        }

        return self::$cache;
    }

    private static function loadAllFromMysql(): array
    {
        $pdo = getPDO();
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->query("SELECT * FROM pohon ORDER BY id ASC");
            $rows = $stmt->fetchAll();
        } catch (PDOException $e) {
            // Table not ready / query failed — fail soft so the map still loads (empty).
            return [];
        }

        $currentYear = (int)date('Y');

        // kesehatan (Sehat/Kurang Sehat/Sakit) -> condition badge used across the UI
        $conditionMap = [
            'Sehat' => 'Baik',
            'Kurang Sehat' => 'Sedang',
            'Sakit' => 'Perlu Perawatan',
        ];

        $trees = array_map(function ($row) use ($currentYear, $conditionMap) {
            $tahunTanam = isset($row['tahun_tanam']) && $row['tahun_tanam'] !== '' ? (int)$row['tahun_tanam'] : null;
            $ageYears = $tahunTanam ? max(0, $currentYear - $tahunTanam) : null;

            $kesehatan = $row['kesehatan'] ?? 'Sehat';
            $condition = $conditionMap[$kesehatan] ?? 'Baik';

            // status_kel holds the planting-program category (RW / Kahati).
            $category = trim((string)($row['status_kel'] ?? ''));
            if (strcasecmp($category, 'RW') !== 0 && strcasecmp($category, 'Kahati') !== 0) {
                $category = 'Kahati';
            }

            $description = trim((string)($row['keterangan'] ?? ''));
            if ($description === '') {
                $parts = [];
                if (!empty($row['family'])) $parts[] = 'famili ' . $row['family'];
                if (!empty($row['habitus'])) $parts[] = 'habitus ' . $row['habitus'];
                if ($tahunTanam) $parts[] = 'ditanam tahun ' . $tahunTanam;
                $description = $parts
                    ? ('Pohon ini termasuk ' . implode(', ', $parts) . '.')
                    : 'Belum ada keterangan tambahan untuk pohon ini.';
            }

            $foto = trim((string)($row['foto'] ?? ''));
            $imageUrl = $foto !== '' ? ('assets/foto/' . $foto) : '';

            return [
                'id' => (int)$row['id'],
                'no_pohon' => $row['no_pohon'] ?? null,
                'name' => $row['nama_lokal'] ?: '(Tanpa nama)',
                'scientific_name' => $row['nama_latin'] ?? '',
                'family' => $row['family'] ?? '',
                'category' => $category,
                'lat' => isset($row['koordinat_y']) && $row['koordinat_y'] !== '' ? (float)$row['koordinat_y'] : null,
                'lng' => isset($row['koordinat_x']) && $row['koordinat_x'] !== '' ? (float)$row['koordinat_x'] : null,
                'address' => $row['nama_jalan'] ?? '',
                'village' => $row['kelurahan'] ?? '',
                'district' => $row['kecamatan'] ?? '',
                'habitus' => $row['habitus'] ?? '',
                'tahun_tanam' => $tahunTanam,
                'age_years' => $ageYears,
                // Not tracked in the real schema (no diameter/height columns) — kept null on purpose.
                'diameter_cm' => null,
                'height_m' => null,
                'volume' => isset($row['volume']) && $row['volume'] !== '' ? (float)$row['volume'] : null,
                'kelas_awet' => $row['kelas_awet'] ?? '',
                'kelas_kuat' => $row['kelas_kuat'] ?? '',
                'berat_jenis' => isset($row['berat_jenis']) && $row['berat_jenis'] !== '' ? (float)$row['berat_jenis'] : null,
                'kesehatan' => $kesehatan,
                'condition' => $condition,
                'serapan_co' => isset($row['serapan_co']) && $row['serapan_co'] !== '' ? (float)$row['serapan_co'] : null,
                'produksi_o' => isset($row['produksi_o']) && $row['produksi_o'] !== '' ? (float)$row['produksi_o'] : null,
                'description' => $description,
                'image_url' => $imageUrl,
            ];
        }, $rows);

        // Drop rows without valid coordinates — Leaflet can't place a marker without lat/lng.
        return array_values(array_filter($trees, fn($t) => $t['lat'] !== null && $t['lng'] !== null));
    }

    public static function all(): array
    {
        return self::loadAll();
    }

    public static function find(int $id): ?array
    {
        foreach (self::loadAll() as $tree) {
            if ((int)$tree['id'] === $id) {
                return $tree;
            }
        }
        return null;
    }

    public static function filterByCategory(string $category): array
    {
        return array_values(array_filter(self::loadAll(), function ($t) use ($category) {
            return strcasecmp($t['category'], $category) === 0;
        }));
    }

    public static function search(string $query): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return self::loadAll();
        }
        return array_values(array_filter(self::loadAll(), function ($t) use ($query) {
            $haystack = mb_strtolower($t['name'] . ' ' . $t['scientific_name'] . ' ' . $t['address'] . ' ' . $t['village'] . ' ' . $t['district']);
            return str_contains($haystack, $query);
        }));
    }

    public static function stats(): array
    {
        $all = self::loadAll();
        $total = count($all);
        $rw = count(array_filter($all, fn($t) => strcasecmp($t['category'], 'RW') === 0));
        $kahati = count(array_filter($all, fn($t) => strcasecmp($t['category'], 'Kahati') === 0));
        $sehat = count(array_filter($all, fn($t) => strcasecmp(trim((string)($t['kesehatan'] ?? '')), 'Sehat') === 0));
        $kurangSehat = count(array_filter($all, fn($t) => strcasecmp(trim((string)($t['kesehatan'] ?? '')), 'Kurang Sehat') === 0));
        $sakit = count(array_filter($all, fn($t) => strcasecmp(trim((string)($t['kesehatan'] ?? '')), 'Sakit') === 0));

        $greenSpacesFile = GEOJSON_PATH . '/green_spaces.geojson';
        $greenSpacesCount = 0;
        if (file_exists($greenSpacesFile)) {
            $geo = json_decode(file_get_contents($greenSpacesFile), true);
            $greenSpacesCount = count($geo['features'] ?? []);
        }

        return [
            'total_trees' => $total,
            'rw_trees' => $rw,
            'kahati_trees' => $kahati,
            'sehat_trees' => $sehat,
            'kurang_sehat_trees' => $kurangSehat,
            'sakit_trees' => $sakit,
            'green_spaces' => $greenSpacesCount,
        ];
    }

    public static function nextId(): int
    {
        $all = self::loadAll();
        $max = 0;
        foreach ($all as $t) {
            $max = max($max, (int)$t['id']);
        }
        return $max + 1;
    }
}