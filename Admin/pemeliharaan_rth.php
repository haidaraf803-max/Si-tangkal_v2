<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'pemeliharaan_rth', 'view');
$canCreate = Rbac::can($config, 'pemeliharaan_rth', 'create');
$canEdit   = Rbac::can($config, 'pemeliharaan_rth', 'edit');
$canDelete = Rbac::can($config, 'pemeliharaan_rth', 'delete');
require_once 'core/PemeliharaanModels.php';

$model     = new PemeliharaanRthModel($config);
$alertMsg  = '';
$alertType = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!$canCreate) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menambah laporan pemeliharaan RTH.';
        $alertType = 'warning';
    } elseif (empty($_FILES['foto']['name'])) {
        // Foto wajib ada
        $alertMsg  = 'Foto wajib diunggah untuk mencatat pemeliharaan RTH.';
        $alertType = 'warning';
    } else {
        $foto = 'rth_' . time() . '_' . basename($_FILES['foto']['name']);
        move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/../assets/foto/' . $foto);
        $ok = $model->create([
            'tanggal'    => $_POST['tanggal'] ?? date('Y-m-d'),
            'personil'   => trim($_POST['personil'] ?? ''),
            'lokasi'     => trim($_POST['lokasi'] ?? ''),
            'kegiatan'   => trim($_POST['kegiatan'] ?? ''),
            'keterangan' => trim($_POST['keterangan'] ?? ''),
            'foto'       => $foto,
        ], $currentUserId);
        $alertMsg  = $ok ? 'Laporan pemeliharaan RTH berhasil disimpan.' : 'Gagal menyimpan laporan.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== EDIT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    if (!$canEdit) {
        $alertMsg  = 'Peran Anda tidak memiliki izin mengubah data pemeliharaan RTH.';
        $alertType = 'warning';
    } else {
        $ok = $model->update((int) $_POST['id'], [
            'tanggal'    => $_POST['tanggal'] ?? date('Y-m-d'),
            'personil'   => trim($_POST['personil'] ?? ''),
            'lokasi'     => trim($_POST['lokasi'] ?? ''),
            'kegiatan'   => trim($_POST['kegiatan'] ?? ''),
            'keterangan' => trim($_POST['keterangan'] ?? ''),
        ]);
        $alertMsg  = $ok ? 'Data pemeliharaan RTH berhasil diperbarui.' : 'Gagal memperbarui data.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    if (!$canDelete) { header('Location: pemeliharaan_rth.php?deleted=forbidden'); exit; }
    $model->delete((int) $_GET['hapus']);
    header('Location: pemeliharaan_rth.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] === 'forbidden' ? 'Peran Anda tidak memiliki izin menghapus data.' : 'Data berhasil dihapus.';
    $alertType = $_GET['deleted'] === 'forbidden' ? 'warning' : 'success';
}

$data = $model->getAll();

$pageTitle  = 'Pemeliharaan RTH';
$activePage = 'pemeliharaan_rth';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<style>
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
    .modal-nice .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; }
    .modal-nice .modal-footer .btn { border-radius: 10px; font-weight: 600; padding: .55rem 1.2rem; font-size: .82rem; }

    .modal-rth .modal-header { background: linear-gradient(135deg,#ecfdf5,#f0fdf4); }
    .modal-rth .icon-badge { background: linear-gradient(135deg,#10b981,#059669); }
    .modal-rth .btn-save { background: linear-gradient(135deg,#10b981,#059669); border: 0; color: #fff; }
    .modal-rth .btn-save:hover { filter: brightness(0.95); color: #fff; }

    .modal-detail .detail-row { display: flex; justify-content: space-between; gap: 1rem; padding: .55rem 0; border-bottom: 1px solid #f1f5f9; }
    .modal-detail .detail-row:last-child { border-bottom: 0; }
    .modal-detail .detail-label { font-size: .74rem; font-weight: 600; letter-spacing: .3px; text-transform: uppercase; color: #94a3b8; flex: 0 0 42%; }
    .modal-detail .detail-value { font-size: .875rem; color: #1a2332; font-weight: 500; text-align: right; flex: 1; word-break: break-word; }
    .modal-detail .detail-photo { width: 100%; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: .5rem; }
    .modal-detail .detail-photo-empty { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1.5rem; text-align: center; color: #94a3b8; font-size: .8rem; margin-top: .5rem; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Pemeliharaan RTH</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Laporan kegiatan pemeliharaan Ruang Terbuka Hijau di lapangan</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($canCreate): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Laporan
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
            <div class="text-muted" style="font-size:0.75rem;">Total Laporan</div>
            <div class="fs-4 fw-bold"><?= count($data) ?></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Riwayat Pemeliharaan RTH</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Personil</th>
                        <th>Lokasi</th>
                        <th>Kegiatan</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></td>
                            <td><?= htmlspecialchars($row['personil']) ?></td>
                            <td><?= htmlspecialchars($row['lokasi']) ?></td>
                            <td><?= htmlspecialchars($row['kegiatan']) ?></td>
                            <td class="text-center pe-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                                        data-bs-toggle="modal" data-bs-target="#modalDetail<?= (int) $row['id'] ?>"
                                        title="Lihat detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($canEdit): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit<?= (int) $row['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                <a href="pemeliharaan_rth.php?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus laporan ini?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada laporan pemeliharaan RTH</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($data as $row): ?>
<!-- Modal Detail -->
<div class="modal fade modal-nice modal-rth modal-detail" id="modalDetail<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="icon-badge"><i class="bi bi-flower3"></i></span>
                    <span>
                        <span class="title-text d-block">Detail Pemeliharaan RTH</span>
                        <span class="modal-subtitle d-block"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></span>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="detail-row"><span class="detail-label">Tanggal</span><span class="detail-value"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></span></div>
                <div class="detail-row"><span class="detail-label">Personil</span><span class="detail-value"><?= htmlspecialchars($row['personil']) ?></span></div>
                <div class="detail-row"><span class="detail-label">Lokasi</span><span class="detail-value"><?= htmlspecialchars($row['lokasi']) ?></span></div>
                <div class="detail-row"><span class="detail-label">Kegiatan</span><span class="detail-value"><?= htmlspecialchars($row['kegiatan']) ?></span></div>
                <div class="detail-row"><span class="detail-label">Petugas Input</span><span class="detail-value"><?= htmlspecialchars($row['petugas_nama'] ?? '—') ?></span></div>
                <div class="detail-row"><span class="detail-label">Keterangan</span><span class="detail-value"><?= $row['keterangan'] ? nl2br(htmlspecialchars($row['keterangan'])) : '—' ?></span></div>
                <?php if (!empty($row['foto'])): ?>
                    <img src="../assets/foto/<?= htmlspecialchars($row['foto']) ?>" class="detail-photo" alt="Foto pemeliharaan RTH">
                <?php else: ?>
                    <div class="detail-photo-empty"><i class="bi bi-image me-1"></i>Tidak ada foto</div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <?php if ($canEdit): ?>
                <button type="button" class="btn btn-save" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalEdit<?= (int) $row['id'] ?>"><i class="bi bi-pencil me-1"></i>Edit</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if ($canEdit): ?>
<?php foreach ($data as $row): ?>
<!-- Modal Edit -->
<div class="modal fade modal-nice modal-rth" id="modalEdit<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="icon-badge"><i class="bi bi-flower3"></i></span>
                    <span>
                        <span class="title-text d-block">Edit Pemeliharaan RTH</span>
                        <span class="modal-subtitle d-block">Perbarui detail laporan (foto tidak dapat diganti di sini)</span>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal" value="<?= htmlspecialchars(date('Y-m-d', strtotime($row['tanggal']))) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Personil <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="personil" value="<?= htmlspecialchars($row['personil']) ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lokasi" value="<?= htmlspecialchars($row['lokasi']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kegiatan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="kegiatan" value="<?= htmlspecialchars($row['kegiatan']) ?>" required>
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
<div class="modal fade modal-nice modal-rth" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="icon-badge"><i class="bi bi-flower3"></i></span>
                    <span class="title-text">Tambah Laporan Pemeliharaan RTH</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Personil <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="personil" placeholder="Nama petugas pelaksana" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lokasi" placeholder="Nama taman / lokasi RTH" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kegiatan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="kegiatan" placeholder="mis. Penyiraman, Penyiangan, Pemangkasan Rumput" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="keterangan" rows="2"></textarea>
                </div>
                <div class="mb-1">
                    <label class="form-label">Foto <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="foto" accept="image/*" required>
                    <div class="form-text">Foto wajib diunggah sebagai bukti kegiatan pemeliharaan.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add" class="btn btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>