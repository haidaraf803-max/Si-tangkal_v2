<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK =====
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'pemakaian_pupuk', 'view');
$canCreate = Rbac::can($config, 'pemakaian_pupuk', 'create');
$canEdit   = Rbac::can($config, 'pemakaian_pupuk', 'edit');
$canDelete = Rbac::can($config, 'pemakaian_pupuk', 'delete');
require_once 'core/PemeliharaanModels.php';

$model     = new PupukModel($config);
$alertMsg  = '';
$alertType = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!$canCreate) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menambah data pemakaian pupuk.';
        $alertType = 'warning';
    } else {
        $foto = null;
        if (!empty($_FILES['foto']['name'])) {
            $foto = 'pupuk_' . time() . '_' . basename($_FILES['foto']['name']);
            move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/../assets/foto/' . $foto);
        }
        $ok = $model->create([
            'tanggal'     => $_POST['tanggal'] ?? date('Y-m-d'),
            'jenis_pupuk' => trim($_POST['jenis_pupuk'] ?? ''),
            'jumlah'      => (float) ($_POST['jumlah'] ?? 0),
            'satuan'      => trim($_POST['satuan'] ?? 'kg'),
            'lokasi'      => trim($_POST['lokasi'] ?? ''),
            'keterangan'  => trim($_POST['keterangan'] ?? ''),
            'foto'        => $foto,
        ], $currentUserId);
        $alertMsg  = $ok ? 'Data pemakaian pupuk berhasil ditambahkan.' : 'Gagal menambahkan data.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    if (!$canDelete) { header('Location: pemakaian_pupuk.php?deleted=forbidden'); exit; }
    $model->delete((int) $_GET['hapus']);
    header('Location: pemakaian_pupuk.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] === 'forbidden' ? 'Peran Anda tidak memiliki izin menghapus data.' : 'Data berhasil dihapus.';
    $alertType = $_GET['deleted'] === 'forbidden' ? 'warning' : 'success';
}

$data = $model->getAll();
$totalBulanIni = 0;
foreach ($data as $row) {
    if (date('Y-m', strtotime($row['tanggal'])) === date('Y-m')) {
        $totalBulanIni += (float) $row['jumlah'];
    }
}

$pageTitle  = 'Pemakaian Pupuk';
$activePage = 'pemakaian_pupuk';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Pemakaian Pupuk</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Catatan pemakaian pupuk untuk pemeliharaan RTH</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php
            $exportModul        = 'pemakaian_pupuk';
            $exportLabel        = 'Pemakaian Pupuk';
            $exportSupportsDate = true;
            require 'layouts/export_modal.php';
        ?>
        <?php if ($canCreate): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Catat Pemakaian
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.75rem;">Total Catatan</div>
            <div class="fs-4 fw-bold"><?= count($data) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.75rem;">Total Bulan Ini</div>
            <div class="fs-4 fw-bold"><?= number_format($totalBulanIni, 1) ?> kg</div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Riwayat Pemakaian Pupuk</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Jenis Pupuk</th>
                        <th class="text-center">Jumlah</th>
                        <th>Lokasi</th>
                        <th>Petugas</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></td>
                            <td><?= htmlspecialchars($row['jenis_pupuk']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['jumlah']) ?> <?= htmlspecialchars($row['satuan']) ?></td>
                            <td><?= htmlspecialchars($row['lokasi']) ?></td>
                            <td><?= htmlspecialchars($row['petugas_nama'] ?? '—') ?></td>
                            <td class="text-center pe-3">
                                <?php if ($canDelete): ?>
                                <a href="pemakaian_pupuk.php?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus catatan ini?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data pemakaian pupuk</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-flower2 text-success me-2"></i>Catat Pemakaian Pupuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis Pupuk <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="jenis_pupuk" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="jumlah" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="satuan">
                            <option value="kg">kg</option>
                            <option value="liter">liter</option>
                            <option value="karung">karung</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lokasi" placeholder="Nama taman / lokasi RTH" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="keterangan" rows="2"></textarea>
                </div>
                <div class="mb-1">
                    <label class="form-label">Foto (opsional)</label>
                    <input type="file" class="form-control" name="foto" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
