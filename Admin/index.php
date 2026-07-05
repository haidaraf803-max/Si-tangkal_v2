<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/functions.php';

// ===== AMBIL DATA DASHBOARD =====
try {
    $stats   = getDashboardStats($config);

    $pengajuanModel = new PengajuanModel($config);
    $pohonModel     = new PohonModel($config);

    $stmtGabungan = $config->query("
        (SELECT Id as id, Nama_Pemohon as nama, No_Surat as info, Lokasi_Pohon as lokasi, Keterangan as status, 'Pemangkasan' as jenis, Disposisi_Surat as tgl FROM pengajuan)
        UNION ALL
        (SELECT id_bibit as id, nama_pemohon as nama, jenis_tanaman as info, lokasi_nanam as lokasi, status_permohonan as status, 'Permohonan Bibit' as jenis, tanggal_permohonan as tgl FROM permohonan_bibit)
        ORDER BY tgl DESC LIMIT 10
    ");
    $latestPengajuan = $stmtGabungan->fetchAll(PDO::FETCH_ASSOC);
    $latestPohon     = $pohonModel->getLatest(10);

    // ===== DATA STOK BIBIT =====
    $stmtTotalStok = $config->query("SELECT SUM(jumlah_tersedia) FROM stok_bibit");
    $totalStok = $stmtTotalStok->fetchColumn() ?: 0;

    $stmtApbd = $config->query("SELECT jenis_tanaman, jumlah_tersedia FROM stok_bibit WHERE sumber_bibit = 'APBD' ORDER BY jumlah_tersedia DESC");
    $stokApbd = $stmtApbd->fetchAll(PDO::FETCH_ASSOC);

    $stmtMandiri = $config->query("SELECT jenis_tanaman, jumlah_tersedia FROM stok_bibit WHERE sumber_bibit = 'Pembibitan Mandiri' ORDER BY jumlah_tersedia DESC");
    $stokMandiri = $stmtMandiri->fetchAll(PDO::FETCH_ASSOC);

    $stmtHibah = $config->query("SELECT jenis_tanaman, jumlah_tersedia FROM stok_bibit WHERE sumber_bibit = 'Hibah' ORDER BY jumlah_tersedia DESC");
    $stokHibah = $stmtHibah->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= DASHBOARD CONTENT ======= -->

<!-- Page Heading -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--text-primary);">Ringkasan Data</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Update data monitoring Si-TANGKAL Kota Cimahi</p>
    </div>
    <span class="badge rounded-pill" style="background:var(--accent-light); color:#0d7a3e; font-size:0.75rem; padding:0.45em 0.9em;">
        <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Live
    </span>
</div>

<!-- ===== 4 STAT CARDS ===== -->
<div class="row g-3 mb-4">

    <!-- Total Pengajuan -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:rgba(13,110,253,0.1); color:#0d6efd;">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div>
                    <div class="stat-number text-primary"><?= $stats['total_pengajuan'] ?></div>
                    <div class="stat-label text-muted">Total Pengajuan</div>
                </div>
            </div>
            <div style="height:3px; background:linear-gradient(90deg,#0d6efd,#6ea8fe); border-radius:0 0 var(--radius-md) var(--radius-md);"></div>
        </div>
    </div>

    <!-- Belum Diproses -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:rgba(220,53,69,0.1); color:#dc3545;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-number text-danger"><?= $stats['belum_proses'] ?></div>
                    <div class="stat-label text-muted">Belum Diproses</div>
                </div>
            </div>
            <div style="height:3px; background:linear-gradient(90deg,#dc3545,#f08080); border-radius:0 0 var(--radius-md) var(--radius-md);"></div>
        </div>
    </div>

    <!-- Pohon Sehat -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:rgba(25,135,84,0.1); color:#198754;">
                    <i class="bi bi-tree-fill"></i>
                </div>
                <div>
                    <div class="stat-number text-success"><?= $stats['pohon_sehat'] ?></div>
                    <div class="stat-label text-muted fw-bold">POHON SEHAT</div>
                </div>
            </div>
            <div style="height:3px; background:linear-gradient(90deg,#198754,#75c997); border-radius:0 0 var(--radius-md) var(--radius-md);"></div>
        </div>
    </div>

    <!-- Pohon Kurang Sehat -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:rgba(253,126,20,0.1); color:#fd7e14;">
                    <i class="bi bi-tree-fill"></i>
                </div>
                <div>
                    <div class="stat-number" style="color:#fd7e14;"><?= $stats['pohon_kurang'] ?></div>
                    <div class="stat-label text-muted fw-bold">POHON KURANG SEHAT</div>
                </div>
            </div>
            <div style="height:3px; background:linear-gradient(90deg,#fd7e14,#ffc078); border-radius:0 0 var(--radius-md) var(--radius-md);"></div>
        </div>
    </div>

    <!-- Pohon Mati -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:rgba(220,53,69,0.1); color:#dc3545;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <div class="stat-number text-danger"><?= $stats['pohon_mati'] ?></div>
                    <div class="stat-label text-muted fw-bold">POHON SAKIT</div>
                </div>
            </div>
            <div style="height:3px; background:linear-gradient(90deg,#dc3545,#ffc078); border-radius:0 0 var(--radius-md) var(--radius-md);"></div>
        </div>
    </div>

    <!-- Total Stok Bibit -->
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:rgba(23,162,184,0.1); color:#17a2b8;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div class="stat-number text-info"><?= number_format($totalStok, 0, ',', '.') ?></div>
                    <div class="stat-label text-muted">Total Stok Bibit</div>
                </div>
            </div>
            <div style="height:3px; background:linear-gradient(90deg,#17a2b8,#90e0ef); border-radius:0 0 var(--radius-md) var(--radius-md);"></div>
        </div>
    </div>

</div>

<!-- ===== ROW: TABEL STOK BIBIT TIGA KOLOM ===== -->
<div class="row g-3 mb-4">
    <!-- Tabel Stok APBD -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-building text-primary me-2"></i>Stok Bibit - APBD</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive overflow-auto" style="max-height: 300px;">
                    <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="ps-3 text-muted text-uppercase">Jenis Tanaman</th>
                                <th class="text-center pe-3 text-muted text-uppercase" style="width: 120px;">Jumlah Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stokApbd)): ?>
                                <?php foreach ($stokApbd as $row): ?>
                                <tr>
                                    <td class="ps-3 fw-500 text-dark"><?= htmlspecialchars($row['jenis_tanaman']) ?></td>
                                    <td class="text-center pe-3">
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                                            <?= (int)$row['jumlah_tersedia'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Belum ada data stok APBD</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Stok Pembibitan Mandiri -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-tree text-success me-2"></i>Stok Bibit - Mandiri</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive overflow-auto" style="max-height: 300px;">
                    <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="ps-3 text-muted text-uppercase">Jenis Tanaman</th>
                                <th class="text-center pe-3 text-muted text-uppercase" style="width: 120px;">Jumlah Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stokMandiri)): ?>
                                <?php foreach ($stokMandiri as $row): ?>
                                <tr>
                                    <td class="ps-3 fw-500 text-dark"><?= htmlspecialchars($row['jenis_tanaman']) ?></td>
                                    <td class="text-center pe-3">
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3">
                                            <?= (int)$row['jumlah_tersedia'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Belum ada data stok Mandiri</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Stok Hibah -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-gift text-warning me-2"></i>Stok Bibit - Hibah</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive overflow-auto" style="max-height: 300px;">
                    <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="ps-3 text-muted text-uppercase">Jenis Tanaman</th>
                                <th class="text-center pe-3 text-muted text-uppercase" style="width: 120px;">Jumlah Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stokHibah)): ?>
                                <?php foreach ($stokHibah as $row): ?>
                                <tr>
                                    <td class="ps-3 fw-500 text-dark"><?= htmlspecialchars($row['jenis_tanaman']) ?></td>
                                    <td class="text-center pe-3">
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3">
                                            <?= (int)$row['jumlah_tersedia'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Belum ada data stok Hibah</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== ROW: DUA TABEL ===== -->
<div class="row g-3">

    <!-- Tabel 10 Pengajuan Terbaru -->
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-file-earmark-text me-2 text-primary"></i>10 Pengajuan Terbaru</span>
                <a href="pengajuan.php" class="btn btn-sm btn-outline-success">
                    Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="cursor:default;">
                        <thead>
                            <tr>
                                <th class="ps-3">Nama Pemohon</th>
                                <th>Jenis Pengajuan</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($latestPengajuan)): ?>
                                <?php foreach ($latestPengajuan as $row): ?>
                                <?php 
                                    $isBibit = ($row['jenis'] === 'Permohonan Bibit');
                                    if ($isBibit) {
                                        $isDone = (strtolower($row['status'] ?? '') === 'disetujui');
                                        $isReject = (strtolower($row['status'] ?? '') === 'ditolak');
                                        $link = "permohonan_bibit.php";
                                    } else {
                                        $isDone = (strtolower($row['status'] ?? '') === 'sudah');
                                        $isReject = false;
                                        $link = "edit_pengajuan.php?id=" . (int)$row['id'];
                                    }
                                ?>
                                <tr onclick="window.location='<?= $link ?>'">
                                    <td class="ps-3 fw-500">
                                        <?= htmlspecialchars($row['nama']) ?>
                                        <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($row['info']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $isBibit ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' ?> mb-1"><?= $row['jenis'] ?></span>
                                        <div class="text-muted" style="max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.75rem;">
                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['lokasi']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isDone): ?>
                                            <span class="badge rounded-pill bg-success-subtle text-success"><?= htmlspecialchars($row['status'] ?: 'Selesai') ?></span>
                                        <?php elseif ($isReject): ?>
                                            <span class="badge rounded-pill bg-danger-subtle text-danger">Ditolak</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-warning-subtle text-warning">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3" onclick="event.stopPropagation()">
                                        <a href="<?= $link ?>" class="btn btn-sm btn-outline-warning" title="Lihat/Edit">
                                            <?= $isBibit ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-pencil"></i>' ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i> Belum ada data pengajuan
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel 10 Pohon Terbaru -->
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-tree me-2 text-success"></i>10 Pohon Terbaru</span>
                <a href="pohon.php" class="btn btn-sm btn-outline-success">
                    Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="cursor:default;">
                        <thead>
                            <tr>
                                <th class="ps-3">Nama Pohon</th>
                                <th>Jenis</th>
                                <th class="text-center pe-3">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($latestPohon)): ?>
                                <?php foreach ($latestPohon as $row):
                                    $kondisi = htmlspecialchars($row['kesehatan']);
                                    $badgeClass = match ($row['kesehatan']) {
                                        'Sehat'        => 'bg-success-subtle text-success',
                                        'Kurang Baik'  => 'bg-warning-subtle text-warning',
                                        'MATI'         => 'bg-danger-subtle text-danger',
                                        default        => 'bg-secondary-subtle text-secondary',
                                    };
                                ?>
                                <tr onclick="window.location='pohon.php'">
                                    <td class="ps-3 fw-500">
                                        <?= htmlspecialchars($row['nama_lokal']) ?>
                                    </td>
                                    <td class="text-muted"><?= htmlspecialchars($row['nama_latin']) ?></td>
                                    <td class="text-center pe-3">
                                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= $kondisi ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="bi bi-tree fs-4 d-block mb-1"></i> Belum ada data pohon
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.row -->

<?php require_once 'layouts/footer.php'; ?>
