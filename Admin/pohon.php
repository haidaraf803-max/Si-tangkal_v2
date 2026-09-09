<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'pohon', 'view');
$canCreatePohon = Rbac::can($config, 'pohon', 'create');
$canEditPohon   = Rbac::can($config, 'pohon', 'edit');
$canDeletePohon = Rbac::can($config, 'pohon', 'delete');
require_once 'core/PohonModel.php';
require_once 'core/PohonPendingModel.php';
require_once 'core/PohonImportHelper.php';

$model        = new PohonModel($config);
$pendingModel = new PohonPendingModel($config);
$currentUserId = $_SESSION['admin']['UserId'] ?? null;
$alertMsg  = '';
$alertType = '';

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    if (!$canDeletePohon) { header('Location: ' . basename(__FILE__) . '?deleted=forbidden'); exit; }
    $hapusId = (int) $_GET['hapus'];
    if ($model->delete($hapusId)) {
        header("Location: pohon.php?deleted=1");
    } else {
        header("Location: pohon.php?deleted=0");
    }
    exit;
}

// Alert dari redirect
if (isset($_GET['deleted'])) {
    if ($_GET['deleted'] === 'forbidden') {
        $alertMsg  = 'Peran Anda tidak memiliki izin menghapus data pohon.';
        $alertType = 'warning';
    } else {
        $alertMsg  = $_GET['deleted'] == '1' ? 'Data pohon berhasil dihapus.' : 'Gagal menghapus data pohon.';
        $alertType = $_GET['deleted'] == '1' ? 'success' : 'danger';
    }
}

// ===== VALIDASI DATA PENDING (pindahkan ke tabel pohon jika valid) =====
if (isset($_GET['validasi'])) {
    if (!$canEditPohon) {
        header('Location: pohon.php?pending=forbidden');
        exit;
    }
    $result = $pendingModel->approve((int) $_GET['validasi'], $model, $currentUserId);
    header('Location: pohon.php?pending=' . ($result['success'] ? 'valid' : 'invalid') . '&msg=' . urlencode($result['message']));
    exit;
}

// ===== TOLAK DATA PENDING (manual) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tolak_pending') {
    if (!$canEditPohon) {
        header('Location: pohon.php?pending=forbidden');
        exit;
    }
    $idPending = (int) ($_POST['id_pending'] ?? 0);
    $catatan   = trim($_POST['catatan_tolak'] ?? '');
    $ok = $pendingModel->reject($idPending, $catatan, $currentUserId);
    header('Location: pohon.php?pending=' . ($ok ? 'rejected' : 'error'));
    exit;
}

// ===== HAPUS DATA PENDING =====
if (isset($_GET['hapus_pending'])) {
    if (!$canDeletePohon) {
        header('Location: pohon.php?pending=forbidden');
        exit;
    }
    $pendingModel->delete((int) $_GET['hapus_pending']);
    header('Location: pohon.php?pending=deleted');
    exit;
}

// Alert dari redirect proses validasi/tolak/hapus pending
if (isset($_GET['pending'])) {
    $map = [
        'forbidden' => ['Peran Anda tidak memiliki izin memvalidasi data pohon.', 'warning'],
        'valid'     => ['Data pending valid & berhasil dipindahkan ke data pohon.', 'success'],
        'invalid'   => ['Data pending tidak valid, ditandai gagal validasi. ' . ($_GET['msg'] ?? ''), 'danger'],
        'rejected'  => ['Data pending berhasil ditolak.', 'success'],
        'deleted'   => ['Data pending berhasil dihapus.', 'success'],
        'error'     => ['Gagal memproses data pending.', 'danger'],
    ];
    if (isset($map[$_GET['pending']])) {
        [$alertMsg, $alertType] = $map[$_GET['pending']];
    }
}

// ===== CREATE =====
// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!$canCreatePohon) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menambah data pohon.';
        $alertType = 'warning';
    } else {

    $nama_lokal   = trim($_POST['nama_lokal'] ?? '');
    $nama_latin   = trim($_POST['nama_latin'] ?? '');
    $family       = trim($_POST['family'] ?? '');
    $kesehatan    = trim($_POST['kesehatan'] ?? '');

    $tahun_tanam  = trim($_POST['tahun_tanam'] ?? '');
    $habitus      = trim($_POST['habitus'] ?? '');
    $status_kel   = trim($_POST['status_kel'] ?? '');
    $volume       = trim($_POST['volume'] ?? '');

    $kelas_awet   = trim($_POST['kelas_awet'] ?? '');
    $kelas_kuat   = trim($_POST['kelas_kuat'] ?? '');

    $berat_jenis  = trim($_POST['berat_jenis'] ?? '');
    $serapan_co   = trim($_POST['serapan_co'] ?? '');
    $produksi_o   = trim($_POST['produksi_o'] ?? '');

    $nama_jalan   = trim($_POST['nama_jalan'] ?? '');
    $kelurahan    = trim($_POST['kelurahan'] ?? '');
    $kecamatan    = trim($_POST['kecamatan'] ?? '');

    // Catatan: form pakai name="latitude" & name="longitude".
    // koordinat_x = longitude (X), koordinat_y = latitude (Y).
    $koordinat_x  = trim($_POST['longitude'] ?? '');
    $koordinat_y  = trim($_POST['latitude'] ?? '');

    $keterangan   = trim($_POST['keterangan'] ?? '');
    $umur_pohon   = trim($_POST['umur_pohon'] ?? '');

    if ($nama_lokal != '' && $kesehatan != '' && $nama_jalan != '' && $koordinat_x !== '' && $koordinat_y !== '') {

        // Catatan: pohon baru TIDAK langsung masuk tabel `pohon`.
        // Disimpan dulu ke staging `pohon_pending`, menunggu divalidasi
        // (tombol "Validasi" pada tabel Data Pending di bawah).
        $fotoName = $model->uploadFoto($_FILES['foto'] ?? null);

        $pendingId = $pendingModel->create([
            'nama_lokal'  => $nama_lokal,
            'nama_latin'  => $nama_latin,
            'family'      => $family,
            'tahun_tanam' => $tahun_tanam,
            'habitus'     => $habitus,
            'status_kel'  => $status_kel,
            'volume'      => $volume,
            'kelas_awet'  => $kelas_awet,
            'kelas_kuat'  => $kelas_kuat,
            'berat_jenis' => $berat_jenis,
            'kesehatan'   => $kesehatan,
            'serapan_co'  => $serapan_co,
            'produksi_o'  => $produksi_o,
            'nama_jalan'  => $nama_jalan,
            'kelurahan'   => $kelurahan,
            'kecamatan'   => $kecamatan,
            'koordinat_x' => $koordinat_x,
            'koordinat_y' => $koordinat_y,
            'keterangan'  => $keterangan,
            'umur_pohon'  => $umur_pohon,
            'foto'        => $fotoName,
        ], $currentUserId);

        if ($pendingId) {
            $alertMsg  = 'Data pohon berhasil dikirim & menunggu validasi sebelum masuk ke Data Pohon (lihat tabel "Data Pending" di bawah).';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Gagal menyimpan pengajuan data pohon.';
            $alertType = 'danger';
        }

    } else {

        $alertMsg  = 'Nama lokal, kondisi kesehatan, nama jalan, latitude, dan longitude wajib diisi.';
        $alertType = 'warning';

    }
    
    }

}

// ===== IMPORT DATA POHON DARI EXCEL/CSV =====
$importSummary = null; // ['sukses' => int, 'gagal' => int, 'errors' => array]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_excel') {
    if (!$canCreatePohon) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menambah data pohon.';
        $alertType = 'warning';
    } elseif (empty($_FILES['file_import']) || ($_FILES['file_import']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $alertMsg  = 'Silakan pilih file (.csv atau .xlsx) untuk diimport.';
        $alertType = 'warning';
    } else {
        $importer = new PohonImportHelper();
        $result   = $importer->readFile($_FILES['file_import']['tmp_name'], $_FILES['file_import']['name']);

        if (!$result['header_ok']) {
            $alertMsg  = 'Import gagal: ' . $result['error'];
            $alertType = 'danger';
        } else {
            $sukses = 0;
            $errors = [];
            foreach ($result['rows'] as $i => $row) {
                $baris = $i + 2; // +2: baris 1 = header, index mulai dari 0
                $err   = $importer->validateRow($row);
                if ($err !== null) {
                    $errors[] = "Baris {$baris}: {$err}";
                    continue;
                }

                $ok = $model->create(
                    $row['nama_lokal'],
                    $row['nama_latin']  ?? '',
                    $row['family']      ?? '',
                    $row['tahun_tanam'] ?? '',
                    $row['habitus']     ?? '',
                    $row['status_kel']  ?? '',
                    (float) str_replace(',', '.', $row['volume']      ?? '0'),
                    $row['kelas_awet']  ?? '',
                    $row['kelas_kuat']  ?? '',
                    (float) str_replace(',', '.', $row['berat_jenis'] ?? '0'),
                    $row['kesehatan'],
                    (float) str_replace(',', '.', $row['serapan_co']  ?? '0'),
                    (float) str_replace(',', '.', $row['produksi_o']  ?? '0'),
                    $row['nama_jalan'],
                    $row['kelurahan']   ?? '',
                    $row['kecamatan']   ?? '',
                    (float) str_replace(',', '.', $row['koordinat_x']),
                    (float) str_replace(',', '.', $row['koordinat_y']),
                    $row['keterangan']  ?? '',
                    $row['umur_pohon']  ?? ''
                );

                if ($ok !== false) {
                    $sukses++;
                } else {
                    $errors[] = "Baris {$baris}: gagal menyimpan ke database";
                }
            }

            $importSummary = ['sukses' => $sukses, 'gagal' => count($errors), 'errors' => $errors];
            $alertType     = ($sukses > 0 && count($errors) === 0) ? 'success' : (($sukses > 0) ? 'warning' : 'danger');
            $alertMsg      = "Import selesai: {$sukses} data berhasil ditambahkan" . (count($errors) ? ', ' . count($errors) . ' baris gagal (lihat rincian di bawah).' : '.');
        }
    }
}

// ===== SEARCH / FILTER (nama lokal, kondisi, nama jalan - bergaya filter Excel) =====
$keyword     = trim($_GET['cari'] ?? '');
$fNamaLokal  = trim($_GET['f_nama_lokal'] ?? '');
$fKondisi    = trim($_GET['f_kondisi'] ?? '');
$fNamaJalan  = trim($_GET['f_nama_jalan'] ?? '');
$isFiltering = ($keyword !== '' || $fNamaLokal !== '' || $fKondisi !== '' || $fNamaJalan !== '');

$data = $isFiltering
    ? $model->filter($keyword, $fNamaLokal, $fKondisi, $fNamaJalan)
    : $model->getAll();

// Opsi dropdown filter (hanya menampilkan nilai yang benar-benar ada di data)
$opsiNamaLokal = $model->getDistinct('nama_lokal');
$opsiKondisi   = $model->getDistinct('kesehatan');
$opsiNamaJalan = $model->getDistinct('nama_jalan');

// Hitung ringkasan kondisi
$totalSehat     = $model->countByKondisi('Sehat');
$totalKurang    = $model->countByKondisi('Kurang Sehat');
$totalMati      = $model->countByKondisi('Sakit');

// ===== DATA PENDING (staging, belum masuk tabel pohon) =====
$pendingData    = $pendingModel->getAll('pending');
$totalPending   = count($pendingData);

$pageTitle  = 'Kondisi Pohon';
$activePage = 'pohon';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Kondisi Pohon</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Kelola dan pantau kondisi pohon di Kota Cimahi</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php
            $exportModul        = 'pohon';
            $exportLabel        = 'Data Pohon';
            $exportSupportsDate = false; // tabel pohon tidak punya kolom tanggal
            require 'layouts/export_modal.php';
        ?>
        <?php if ($canCreatePohon): ?>
        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
            <i class="bi bi-file-earmark-excel me-1"></i> Import Excel
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pohon
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-arrow-repeat me-1"></i> Sync To C-Map
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Mini Stat Row -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-tree-fill text-success fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalSehat ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Sehat</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-tree text-warning fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalKurang ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Kurang Sehat</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalMati ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Sakit</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert -->
<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $alertType === 'success' ? 'check-circle' : ($alertType === 'danger' ? 'x-circle' : 'exclamation-triangle') ?> me-2"></i>
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Rincian hasil Import Excel (kalau ada baris gagal) -->
<?php if ($importSummary && !empty($importSummary['errors'])): ?>
<div class="alert alert-warning">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Rincian baris gagal diimport:</div>
    <ul class="mb-0" style="font-size:0.85rem; max-height:200px; overflow-y:auto;">
        <?php foreach ($importSummary['errors'] as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- ======= FILTER (bergaya Excel: dropdown dari data yang ada) ======= -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Nama Lokal</label>
                <select name="f_nama_lokal" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <?php foreach ($opsiNamaLokal as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>" <?= $fNamaLokal === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Kondisi</label>
                <select name="f_kondisi" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <?php foreach ($opsiKondisi as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>" <?= $fKondisi === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Nama Jalan</label>
                <select name="f_nama_jalan" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <?php foreach ($opsiNamaJalan as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>" <?= $fNamaJalan === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-success flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <?php if ($isFiltering): ?>
                <a href="pohon.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ======= TABLE ======= -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-table me-2"></i>Data Pohon
            <span class="badge bg-secondary ms-1"><?= count($data) ?></span>
        </span>
        <form method="GET" class="d-flex gap-2" style="min-width:240px;">
            <input type="hidden" name="f_nama_lokal" value="<?= htmlspecialchars($fNamaLokal) ?>">
            <input type="hidden" name="f_kondisi" value="<?= htmlspecialchars($fKondisi) ?>">
            <input type="hidden" name="f_nama_jalan" value="<?= htmlspecialchars($fNamaJalan) ?>">
            <input type="text" name="cari" class="form-control form-control-sm"
                   placeholder="Cari nama / lokasi / kesehatan..."
                   value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-search"></i></button>
            <?php if ($keyword): ?>
            <a href="pohon.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No</th>
                        <th>Nama Lokal</th>
                        <th>Nama Latin</th>
                        <th>Family</th>
                        <th class="text-center">Kondisi</th>
                        <th>Lokasi</th>
                        <th>Keterangan</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php $no = 1; foreach ($data as $row):
                            $kondisi = $row['kesehatan'];
                            $badgeClass = match ($kondisi) {
                                'Sehat'        => 'bg-success-subtle text-success',
                                'Kurang Sehat'  => 'bg-warning-subtle text-warning',
                                'Sakit'         => 'bg-danger-subtle text-danger',
                                default        => 'bg-secondary-subtle text-secondary',
                            };
                        ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['nama_lokal']) ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['nama_latin']) ?></td>
                            <td><?= htmlspecialchars($row['family']) ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($kondisi) ?></span>
                            </td>
                            <td>
                                <span style="max-width:200px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                      title="<?= htmlspecialchars($row['nama_jalan']) ?>">
                                    <?= htmlspecialchars($row['nama_jalan']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="max-width:160px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                      title="<?= htmlspecialchars($row['keterangan']) ?>">
                                    <?= htmlspecialchars($row['keterangan'] ?: '—') ?>
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <a href="detail_pohon.php?id=<?= (int)$row['id'] ?>"
                                   class="btn btn-sm btn-outline-info"
                                   title="Detail"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-info"></i>
                                </a>
                                <?php if ($canEditPohon): ?>
                                <a href="edit_pohon.php?id=<?= (int)$row['id'] ?>"
                                   class="btn btn-sm btn-outline-warning"
                                   title="Edit"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($canDeletePohon): ?>
                                <a href="pohon.php?hapus=<?= (int)$row['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Hapus"
                                   onclick="event.stopPropagation(); return confirm('Yakin ingin menghapus data pohon ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-tree fs-2 d-block mb-2"></i>
                                <?= $keyword ? "Tidak ditemukan data untuk \"<strong>" . htmlspecialchars($keyword) . "</strong>\"" : 'Belum ada data pohon' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======= TABLE: DATA PENDING (belum masuk tabel pohon) ======= -->
<div class="card mt-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-hourglass-split me-2"></i>Data Pending (Menunggu Validasi)
            <span class="badge bg-warning text-dark ms-1"><?= $totalPending ?></span>
        </span>
        <?php
            $exportModul        = 'pohon_pending';
            $exportLabel        = 'Data Pohon Pending';
            $exportSupportsDate = true; // pohon_pending punya kolom dibuat_pada
            require 'layouts/export_modal.php';
        ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No</th>
                        <th>Nama Lokal</th>
                        <th class="text-center">Kondisi</th>
                        <th>Lokasi</th>
                        <th>Dikirim Oleh</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendingData)): ?>
                        <?php $no = 1; foreach ($pendingData as $row): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['nama_lokal']) ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary"><?= htmlspecialchars($row['kesehatan']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['nama_jalan']) ?></td>
                            <td><?= htmlspecialchars($row['dibuat_oleh_nama'] ?? '—') ?></td>
                            <td class="text-center pe-3">
                                <?php if ($canEditPohon): ?>
                                <a href="pohon.php?validasi=<?= (int) $row['id'] ?>"
                                   class="btn btn-sm btn-outline-success"
                                   title="Validasi & pindahkan ke Data Pohon"
                                   onclick="return confirm('Validasi data ini? Jika lolos validasi, data akan dipindahkan ke Data Pohon.')">
                                    <i class="bi bi-check-lg"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Tolak"
                                        data-bs-toggle="modal" data-bs-target="#modalTolak"
                                        onclick="document.getElementById('tolak_id_pending').value = '<?= (int) $row['id'] ?>'">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($canDeletePohon): ?>
                                <a href="pohon.php?hapus_pending=<?= (int) $row['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus pengajuan ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Tidak ada data pohon yang menunggu validasi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======= MODAL: TOLAK DATA PENDING ======= -->
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="tolak_pending">
                <input type="hidden" name="id_pending" id="tolak_id_pending" value="">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-2 text-danger"></i>Tolak Data Pending</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea name="catatan_tolak" class="form-control" rows="3" placeholder="Contoh: koordinat di luar wilayah Cimahi"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======= MODAL: TAMBAH POHON ======= -->
<!-- ======= MODAL: IMPORT EXCEL ======= -->
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-labelledby="modalImportExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_excel">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalImportExcelLabel">
                        <i class="bi bi-file-earmark-excel me-2"></i>Import Data Pohon dari Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size:0.85rem;">
                        Import banyak data pohon sekaligus dari file <strong>.xlsx</strong> atau <strong>.csv</strong>.
                        Gunakan format template di bawah supaya kolom terbaca dengan benar. Data yang berhasil
                        divalidasi akan langsung masuk ke Data Pohon.
                    </p>
                    <a href="template_import_pohon.php" class="btn btn-outline-success btn-sm mb-3">
                        <i class="bi bi-download me-1"></i> Download Template
                    </a>
                    <div class="mb-2">
                        <label class="form-label" for="file_import">Pilih File</label>
                        <input type="file" id="file_import" name="file_import" class="form-control" accept=".csv,.xlsx" required>
                        <div class="form-text">Format: CSV atau XLSX. Baris pertama harus header sesuai template.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i> Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:var(--radius-lg); border:none; box-shadow:var(--shadow-lg);">
            <form method="POST" enctype="multipart/form-data">

                <input type="hidden" name="action" value="create">

                <div class="modal-header" style="border-bottom:1px solid var(--border-color);">
                    <h5 class="modal-title fw-bold" id="modalTambahLabel">
                        <i class="bi bi-tree me-2 text-success"></i>
                        Tambah Data Pohon
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body py-3">

                    <div class="row g-3">

                        <!-- Nama Lokal -->
                        <div class="col-md-6">
                            <label class="form-label" for="nama_lokal">
                                Nama Lokal <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="nama_lokal"
                                name="nama_lokal"
                                class="form-control"
                                placeholder="Contoh: Pohon Mangga"
                                required>
                        </div>

                        <!-- Nama Latin -->
                        <div class="col-md-6">
                            <label class="form-label" for="nama_latin">
                                Nama Latin
                            </label>

                            <input
                                type="text"
                                id="nama_latin"
                                name="nama_latin"
                                class="form-control"
                                placeholder="Contoh: Mangifera indica">
                        </div>

                        <!-- Family -->
                        <div class="col-md-6">
                            <label class="form-label" for="family">
                                Family
                            </label>

                            <input
                                type="text"
                                id="family"
                                name="family"
                                class="form-control"
                                placeholder="Contoh: Moraceae">
                        </div>

                        <!-- Kondisi -->
                        <div class="col-md-6">
                            <label class="form-label" for="kesehatan">
                                Kondisi Kesehatan <span class="text-danger">*</span>
                            </label>

                            <select
                                id="kesehatan"
                                name="kesehatan"
                                class="form-select"
                                required>

                                <option value="">-- Pilih Kondisi --</option>
                                <option value="Sehat">Sehat</option>
                                <option value="Kurang Sehat">Kurang Sehat</option>
                                <option value="Sakit">Sakit</option>

                            </select>
                        </div>

                        <!-- Tahun Tanam -->
                        <div class="col-md-6">
                            <label class="form-label" for="tahun_tanam">
                                Tahun Tanam
                            </label>

                            <input
                                type="number"
                                id="tahun_tanam"
                                name="tahun_tanam"
                                class="form-control"
                                placeholder="Contoh: 2020">
                        </div>
                         <div class="col-md-6">
                            <label class="form-label" for="umur_pohon">
                                Umur Pohon
                            </label>

                            <input
                                type="number"
                                id="umur_pohon"
                                name="umur_pohon"
                                class="form-control"
                                placeholder="34">
                        </div>

                        <!-- Habitus -->
                        <div class="col-md-6">
                            <label class="form-label" for="habitus">
                                Habitus
                            </label>

                            <input
                                type="text"
                                id="habitus"
                                name="habitus"
                                class="form-control"
                                placeholder="Masukkan habitus pohon">
                        </div>

                        <!-- Status Kelompok -->
                        <div class="col-md-6">
                            <label class="form-label" for="status_kel">
                                Status Kelompok
                            </label>

                            <input
                                type="text"
                                id="status_kel"
                                name="status_kel"
                                class="form-control"
                                placeholder="Contoh: Least Concern">
                        </div>

                        <!-- Volume -->
                        <div class="col-md-6">
                            <label class="form-label" for="volume">
                                Volume
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                id="volume"
                                name="volume"
                                class="form-control"
                                placeholder="Contoh: 0.2310123">
                        </div>

                        <!-- Kelas Awet -->
                        <div class="col-md-6">
                            <label class="form-label" for="kelas_awet">
                                Kelas Awet
                            </label>

                            <select
                                id="kelas_awet"
                                name="kelas_awet"
                                class="form-select">

                                <option value="">-- Pilih Kelas --</option>
                                <option value="I">I</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>

                            </select>
                        </div>

                        <!-- Kelas Kuat -->
                        <div class="col-md-6">
                            <label class="form-label" for="kelas_kuat">
                                Kelas Kuat
                            </label>

                            <select
                                id="kelas_kuat"
                                name="kelas_kuat"
                                class="form-select">

                                <option value="">-- Pilih Kelas --</option>
                                <option value="I">I</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>

                            </select>
                        </div>

                        <!-- Berat Jenis -->
                        <div class="col-md-6">
                            <label class="form-label" for="berat_jenis">
                                Berat Jenis
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                id="berat_jenis"
                                name="berat_jenis"
                                class="form-control"
                                placeholder="Contoh: 310.0123">
                        </div>

                        <!-- Serapan CO -->
                        <div class="col-md-6">
                            <label class="form-label" for="serapan_co">
                                Serapan CO₂
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                id="serapan_co"
                                name="serapan_co"
                                class="form-control"
                                placeholder="Contoh: 1124.1234503">
                        </div>

                        <!-- Produksi O -->
                        <div class="col-md-6">
                            <label class="form-label" for="produksi_o">
                                Produksi O₂
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                id="produksi_o"
                                name="produksi_o"
                                class="form-control"
                                placeholder="Contoh: 1000.012343">
                        </div>

                        <!-- Nama Jalan -->
                        <div class="col-md-12">
                            <label class="form-label" for="nama_jalan">
                                Nama Jalan <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="nama_jalan"
                                name="nama_jalan"
                                class="form-control"
                                placeholder="Alamat lokasi pohon"
                                required>
                        </div>

                        <!-- Kelurahan -->
                        <div class="col-md-6">
                            <label class="form-label" for="kelurahan">
                                Kelurahan
                            </label>

                            <input
                                type="text"
                                id="kelurahan"
                                name="kelurahan"
                                class="form-control"
                                placeholder="Kelurahan lokasi pohon">
                        </div>

                        <!-- Kecamatan -->
                        <div class="col-md-6">
                            <label class="form-label" for="kecamatan">
                                Kecamatan
                            </label>

                            <input
                                type="text"
                                id="kecamatan"
                                name="kecamatan"
                                class="form-control"
                                placeholder="Kecamatan lokasi pohon">
                        </div>

                        <!-- Latitude -->
                        <div class="col-md-6">
                            <label class="form-label" for="latitude">
                                Latitude (Y) <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="latitude"
                                name="latitude"
                                class="form-control"
                                placeholder="-6.88912345"
                                required>
                        </div>

                        <!-- Longitude -->
                        <div class="col-md-6">
                            <label class="form-label" for="longitude">
                                Longitude (X) <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="longitude"
                                name="longitude"
                                class="form-control"
                                placeholder="107.54123456"
                                required>
                        </div>

                        <!-- Foto -->
                        <div class="col-12">
                            <label class="form-label" for="foto">
                                Foto Pohon
                            </label>

                            <input
                                type="file"
                                id="foto"
                                name="foto"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp">
                            <div class="form-text">Format: JPG, JPEG, PNG, WEBP.</div>
                        </div>

                        <!-- Keterangan -->
                        <div class="col-12">
                            <label class="form-label" for="keterangan">
                                Keterangan
                            </label>

                            <textarea
                                id="keterangan"
                                name="keterangan"
                                class="form-control"
                                rows="2"
                                placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer" style="border-top:1px solid var(--border-color);">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success">
                        <i class="bi bi-save me-1"></i>
                        Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>