<?php
/**
 * Admin/export.php
 * ---------------------------------------------------------
 * Endpoint tunggal untuk ekspor CSV semua halaman data di Admin.
 * Dipakai lewat tombol "Export CSV" pada tiap halaman (pohon,
 * pengajuan, monitoring, dll). Mendukung 3 mode:
 *
 *   mode=all      -> seluruh data
 *   mode=tanggal  -> data pada 1 tanggal tertentu (?tanggal=YYYY-MM-DD)
 *   mode=rentang  -> data di antara 2 tanggal (?dari=&sampai=)
 *
 * Contoh:
 *   export.php?modul=pohon&mode=all
 *   export.php?modul=pengajuan&mode=tanggal&tanggal=2026-07-08
 *   export.php?modul=monitoring&mode=rentang&dari=2026-07-01&sampai=2026-07-31
 * ---------------------------------------------------------
 */

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once __DIR__ . '/core/Rbac.php';
require_once __DIR__ . '/core/CsvExportHelper.php';

/**
 * Definisi tiap modul yang bisa diekspor:
 *   table       -> nama tabel/subquery sumber data
 *   date_column -> kolom tanggal untuk filter (null = tidak didukung filter tanggal)
 *   date_is_datetime -> true jika kolom bertipe datetime/timestamp (perlu DATE())
 *   order_by    -> pengurutan default
 *   columns     -> [kolom_db => Judul Kolom di CSV]
 */
$modules = [
    'pohon' => [
        'menu'      => 'pohon',
        'table'     => 'pohon',
        'date_column' => null,
        'order_by'  => 'id ASC',
        'columns'   => [
            'id' => 'ID', 'no_pohon' => 'No Pohon', 'nama_lokal' => 'Nama Lokal',
            'nama_latin' => 'Nama Latin', 'family' => 'Family', 'tahun_tanam' => 'Tahun Tanam',
            'umur_pohon' => 'Umur Pohon',
            'habitus' => 'Habitus', 'status_kel' => 'Status Kelurahan', 'kesehatan' => 'Kesehatan',
            'nama_jalan' => 'Nama Jalan', 'kelurahan' => 'Kelurahan', 'kecamatan' => 'Kecamatan',
            'koordinat_x' => 'Longitude', 'koordinat_y' => 'Latitude', 'keterangan' => 'Keterangan',
        ],
    ],
    'pengajuan' => [
        'menu'      => 'pengajuan',
        'table'     => 'pengajuan',
        'date_column' => 'Disposisi_Surat',
        'date_is_datetime' => false,
        'order_by'  => 'Disposisi_Surat DESC, Id DESC',
        'columns'   => [
            'Id' => 'ID', 'No_Surat' => 'No Surat', 'Nama_Pemohon' => 'Nama Pemohon',
            'Nomor_Telepon' => 'Nomor Telepon', 'Lokasi_Pohon' => 'Lokasi Pohon',
            'Disposisi_Surat' => 'Tanggal Pengajuan', 'Tanggal_Penanganan' => 'Tanggal Penanganan',
            'Keterangan' => 'Status',
        ],
    ],
    'monitoring' => [
        'menu'      => 'monitoring',
        'table'     => 'monitoring',
        'date_column' => 'tanggal_monitoring',
        'date_is_datetime' => true,
        'order_by'  => 'tanggal_monitoring DESC, id DESC',
        'columns'   => [
            'id' => 'ID', 'pohon_id' => 'ID Pohon', 'tanggal_monitoring' => 'Tanggal Monitoring',
            'kesehatan_monitoring' => 'Kesehatan', 'tinggi_pohon' => 'Tinggi (m)',
            'diameter_batang' => 'Diameter (cm)', 'lebar_tajuk' => 'Lebar Tajuk (m)',
            'jenis_gangguan' => 'Jenis Gangguan', 'tingkat_keparahan' => 'Tingkat Keparahan',
            'rekomendasi_tindakan' => 'Rekomendasi Tindakan', 'status_tindak_lanjut' => 'Status Tindak Lanjut',
            'catatan' => 'Catatan',
        ],
    ],
    'laporan_penanaman' => [
        'menu'      => 'laporan_penanaman',
        'table'     => 'laporan_penanaman',
        'date_column' => 'tanggal',
        'date_is_datetime' => false,
        'order_by'  => 'tanggal DESC, id DESC',
        'columns'   => [
            'id' => 'ID', 'tanggal' => 'Tanggal', 'lokasi' => 'Lokasi', 'latitude' => 'Latitude',
            'longitude' => 'Longitude', 'asal_bibit' => 'Asal Bibit', 'jenis_tanaman' => 'Jenis Tanaman',
            'jumlah_bibit' => 'Jumlah Bibit', 'keterangan' => 'Keterangan',
        ],
    ],
    'pemakaian_bbm' => [
        'menu'      => 'pemakaian_bbm',
        'table'     => 'pemakaian_bbm',
        'date_column' => 'tanggal',
        'date_is_datetime' => false,
        'order_by'  => 'tanggal DESC, id DESC',
        'columns'   => [
            'id' => 'ID', 'tanggal' => 'Tanggal', 'jenis_bbm' => 'Jenis BBM',
            'jumlah_liter' => 'Jumlah (Liter)', 'nominal_rupiah' => 'Nominal (Rp)',
            'kendaraan' => 'Kendaraan', 'keterangan' => 'Keterangan',
        ],
    ],
    'pemakaian_pupuk' => [
        'menu'      => 'pemakaian_pupuk',
        'table'     => 'pemakaian_pupuk',
        'date_column' => 'tanggal',
        'date_is_datetime' => false,
        'order_by'  => 'tanggal DESC, id DESC',
        'columns'   => [
            'id' => 'ID', 'tanggal' => 'Tanggal', 'jenis_pupuk' => 'Jenis Pupuk',
            'jumlah' => 'Jumlah', 'satuan' => 'Satuan', 'lokasi' => 'Lokasi', 'keterangan' => 'Keterangan',
        ],
    ],
    'stok_bibit' => [
        'menu'      => 'stok_bibit',
        'table'     => 'stok_bibit',
        'date_column' => 'tanggal_update',
        'date_is_datetime' => true,
        'order_by'  => 'jenis_tanaman ASC',
        'columns'   => [
            'id_stok' => 'ID', 'jenis_tanaman' => 'Jenis Tanaman', 'jumlah_tersedia' => 'Jumlah Tersedia',
            'sumber_bibit' => 'Sumber Bibit', 'tanggal_update' => 'Terakhir Diperbarui',
        ],
    ],
    'permintaan_sarpras' => [
        'menu'      => 'permintaan_sarpras',
        'table'     => 'permintaan_sarpras',
        'date_column' => 'tanggal',
        'date_is_datetime' => false,
        'order_by'  => 'tanggal DESC, id DESC',
        'columns'   => [
            'id' => 'ID', 'tanggal' => 'Tanggal', 'nama_barang' => 'Nama Barang', 'jumlah' => 'Jumlah',
            'satuan' => 'Satuan', 'alasan' => 'Alasan', 'status' => 'Status', 'catatan_admin' => 'Catatan Admin',
        ],
    ],
    'permohonan_bibit' => [
        'menu'      => 'permohonan_bibit',
        'table'     => 'permohonan_bibit',
        'date_column' => 'tanggal_permohonan',
        'date_is_datetime' => false,
        'order_by'  => 'tanggal_permohonan DESC, id_bibit DESC',
        'columns'   => [
            'id_bibit' => 'ID', 'nama_pemohon' => 'Nama Pemohon', 'nomor_telepon' => 'Nomor Telepon',
            'jenis_tanaman' => 'Jenis Tanaman', 'jumlah_tanaman' => 'Jumlah Tanaman',
            'lokasi_nanam' => 'Lokasi Tanam', 'tanggal_permohonan' => 'Tanggal Permohonan',
            'status_permohonan' => 'Status', 'keterangan' => 'Keterangan',
        ],
    ],
    'pergantian_pohon' => [
        'menu'      => 'pergantian_pohon',
        'table'     => 'pergantian_pohon',
        'date_column' => 'created_at',
        'date_is_datetime' => true,
        'order_by'  => 'created_at DESC, id DESC',
        'columns'   => [
            'id' => 'ID', 'jenis_pohon' => 'Jenis Pohon', 'diameter_cm' => 'Diameter (cm)',
            'jumlah_pohon' => 'Jumlah Pohon', 'harga_per_cm' => 'Harga per cm', 'total_biaya' => 'Total Biaya',
            'nomor_surat' => 'Nomor Surat', 'tanggal_surat' => 'Tanggal Surat', 'created_at' => 'Dibuat Pada',
        ],
    ],
    'users' => [
        'menu'      => 'users',
        'table'     => 't_users',
        'date_column' => 'CreatedDate',
        'date_is_datetime' => true,
        'order_by'  => 'UserId ASC',
        'columns'   => [
            'UserId' => 'ID', 'Username' => 'Username', 'Name' => 'Nama', 'Email' => 'Email',
            'Type' => 'Tipe/Role', 'CreatedDate' => 'Tanggal Dibuat',
        ],
    ],
];

$modul = trim($_GET['modul'] ?? '');
if (!isset($modules[$modul])) {
    http_response_code(400);
    exit('Modul export tidak dikenali.');
}
$cfg = $modules[$modul];

// ===== RBAC: user hanya boleh export data yang boleh ia lihat =====
Rbac::requireAccess($config, $cfg['menu'], 'view');

$mode    = trim($_GET['mode'] ?? 'all');
$tanggal = trim($_GET['tanggal'] ?? '');
$dari    = trim($_GET['dari'] ?? '');
$sampai  = trim($_GET['sampai'] ?? '');

$where  = '';
$params = [];

if ($mode === 'tanggal' && $cfg['date_column'] !== null) {
    if (!CsvExportHelper::isValidDate($tanggal)) {
        http_response_code(400);
        exit('Tanggal tidak valid. Gunakan format YYYY-MM-DD.');
    }
    $col = $cfg['date_column'];
    $where = !empty($cfg['date_is_datetime']) ? "WHERE DATE(`$col`) = :tgl" : "WHERE `$col` = :tgl";
    $params[':tgl'] = $tanggal;
} elseif ($mode === 'rentang' && $cfg['date_column'] !== null) {
    if (!CsvExportHelper::isValidDate($dari) || !CsvExportHelper::isValidDate($sampai)) {
        http_response_code(400);
        exit('Rentang tanggal tidak valid. Gunakan format YYYY-MM-DD.');
    }
    if ($dari > $sampai) {
        [$dari, $sampai] = [$sampai, $dari];
    }
    $col = $cfg['date_column'];
    $where = !empty($cfg['date_is_datetime']) ? "WHERE DATE(`$col`) BETWEEN :dari AND :sampai" : "WHERE `$col` BETWEEN :dari AND :sampai";
    $params[':dari']   = $dari;
    $params[':sampai'] = $sampai;
} else {
    // mode=all (atau modul tidak punya kolom tanggal untuk difilter)
    $mode = 'all';
}

$dbColumns = array_keys($cfg['columns']);
$colList   = implode(', ', array_map(fn($c) => "`$c`", $dbColumns));

$sql = "SELECT $colList FROM `{$cfg['table']}` $where ORDER BY {$cfg['order_by']}";
$stmt = $config->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rows = [];
foreach ($data as $r) {
    $line = [];
    foreach ($dbColumns as $c) {
        $line[] = $r[$c] ?? '';
    }
    $rows[] = $line;
}

$filename = CsvExportHelper::buildFilename($modul, $mode, $tanggal, $dari, $sampai);
CsvExportHelper::stream($filename, array_values($cfg['columns']), $rows);
