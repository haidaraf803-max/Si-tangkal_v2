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

// ===== EDIT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    if (!$canEdit) {
        $alertMsg  = 'Peran Anda tidak memiliki izin mengubah data pemakaian pupuk.';
        $alertType = 'warning';
    } else {
        $ok = $model->update((int) $_POST['id'], [
            'tanggal'     => $_POST['tanggal'] ?? date('Y-m-d'),
            'jenis_pupuk' => trim($_POST['jenis_pupuk'] ?? ''),
            'jumlah'      => (float) ($_POST['jumlah'] ?? 0),
            'satuan'      => trim($_POST['satuan'] ?? 'kg'),
            'lokasi'      => trim($_POST['lokasi'] ?? ''),
            'keterangan'  => trim($_POST['keterangan'] ?? ''),
        ]);
        $alertMsg  = $ok ? 'Data pemakaian pupuk berhasil diperbarui.' : 'Gagal memperbarui data.';
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

<style>
    /* ===== Modal cantik terpakai bersama (pupuk/bbm/sarpras) — struktur Bootstrap standar, aman dari flex pecah ===== */
    .modal-nice .modal-content {
        display: flex !important;
        flex-direction: column !important;
        border: 0; border-radius: 18px; overflow: hidden;
        box-shadow: 0 20px 60px rgba(15,23,42,.18);
    }
    .modal-nice .modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); }
    .modal-nice .modal-title { display: flex; align-items: center; gap: .75rem; margin: 0; }
    .modal-nice .icon-badge {
        width: 40px; height: 40px; border-radius: 12px; flex: 0 0 auto;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff;
    }
    .modal-nice .title-text { font-size: 1rem; font-weight: 700; color: var(--text-primary); line-height: 1.3; }
    .modal-nice .modal-subtitle { font-size: .76rem; font-weight: 400; color: var(--text-muted); margin-top: .1rem; }
    .modal-nice .modal-body { padding: 1.5rem; background: #fff; }
    .modal-nice .form-label { font-weight: 600; font-size: .74rem; letter-spacing: .3px; text-transform: uppercase; color: #475569; margin-bottom: .4rem; }
    .modal-nice .form-control, .modal-nice .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: .55rem .8rem; font-size: .875rem; }
    .modal-nice .form-control:focus, .modal-nice .form-select:focus { box-shadow: 0 0 0 3px rgba(5,150,105,.12); }
    .modal-nice .input-group-text { border-radius: 10px 0 0 10px; border: 1.5px solid #e2e8f0; border-right: 0; background: #f8fafc; color: #64748b; }
    .modal-nice .input-group .form-control, .modal-nice .input-group .form-select { border-radius: 0 10px 10px 0; }
    .modal-nice .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; }
    .modal-nice .modal-footer .btn { border-radius: 10px; font-weight: 600; padding: .55rem 1.2rem; font-size: .82rem; }
    .modal-nice .form-text { font-size: .72rem; }

    .modal-pupuk .modal-header { background: linear-gradient(135deg,#ecfdf5,#f0fdf4); }
    .modal-pupuk .icon-badge { background: linear-gradient(135deg,#10b981,#059669); }
    .modal-pupuk .btn-save { background: linear-gradient(135deg,#10b981,#059669); border: 0; color: #fff; }
    .modal-pupuk .btn-save:hover { filter: brightness(0.95); color: #fff; }
    .modal-pupuk .form-control:focus, .modal-pupuk .form-select:focus { border-color: #10b981; }
</style>

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
                                <?php if ($canEdit): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit<?= (int) $row['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php endif; ?>
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

<?php if ($canEdit): ?>
<?php foreach ($data as $row): ?>
<!-- Modal Edit -->
<div class="modal fade modal-nice modal-pupuk" id="modalEdit<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="icon-badge"><i class="bi bi-flower2"></i></span>
                    <span>
                        <span class="title-text d-block">Edit Pemakaian Pupuk</span>
                        <span class="modal-subtitle d-block">Perbarui detail catatan pemupukan</span>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control" name="tanggal" value="<?= htmlspecialchars(date('Y-m-d', strtotime($row['tanggal']))) ?>" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Jenis Pupuk <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="jenis_pupuk" value="<?= htmlspecialchars($row['jenis_pupuk']) ?>" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-7">
                        <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="jumlah" value="<?= htmlspecialchars($row['jumlah']) ?>" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="satuan">
                            <?php foreach (['kg', 'liter', 'karung'] as $sat): ?>
                            <option value="<?= $sat ?>" <?= $row['satuan'] === $sat ? 'selected' : '' ?>><?= $sat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" class="form-control" name="lokasi" value="<?= htmlspecialchars($row['lokasi']) ?>" required>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="keterangan" rows="2"><?= htmlspecialchars($row['keterangan'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit" class="btn btn-save"><i class="bi bi-check2-circle me-1"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

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