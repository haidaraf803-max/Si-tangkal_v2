<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'permintaan_sarpras', 'view');
$canCreate = Rbac::can($config, 'permintaan_sarpras', 'create');
$canEdit   = Rbac::can($config, 'permintaan_sarpras', 'edit'); // dipakai utk menanggapi status
$canDelete = Rbac::can($config, 'permintaan_sarpras', 'delete');
require_once 'core/PemeliharaanModels.php';

$model     = new SarprasModel($config);
$alertMsg  = '';
$alertType = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== CREATE (ajukan permintaan) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!$canCreate) {
        $alertMsg  = 'Peran Anda tidak memiliki izin mengajukan permintaan sarpras.';
        $alertType = 'warning';
    } else {
        $ok = $model->create([
            'tanggal'     => $_POST['tanggal'] ?? date('Y-m-d'),
            'terima_dari' => trim($_POST['terima_dari'] ?? ''),
            'penerima'    => trim($_POST['penerima'] ?? ''),
            'nama_barang' => trim($_POST['nama_barang'] ?? ''),
            'jumlah'      => (int) ($_POST['jumlah'] ?? 1),
            'satuan'      => trim($_POST['satuan'] ?? 'unit'),
            'alasan'      => trim($_POST['alasan'] ?? ''),
        ], $currentUserId);
        $alertMsg  = $ok ? 'Permintaan sarpras berhasil diajukan.' : 'Gagal mengajukan permintaan.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== TANGGAPI (ubah status) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tanggapi'])) {
    if (!$canEdit) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menanggapi permintaan sarpras.';
        $alertType = 'warning';
    } else {
        $ok = $model->tanggapi((int) $_POST['id'], $_POST['status'] ?? '', trim($_POST['catatan_admin'] ?? ''));
        $alertMsg  = $ok ? 'Status permintaan berhasil diperbarui.' : 'Gagal memperbarui status.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    if (!$canDelete) { header('Location: permintaan_sarpras.php?deleted=forbidden'); exit; }
    $model->delete((int) $_GET['hapus']);
    header('Location: permintaan_sarpras.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] === 'forbidden' ? 'Peran Anda tidak memiliki izin menghapus data.' : 'Data berhasil dihapus.';
    $alertType = $_GET['deleted'] === 'forbidden' ? 'warning' : 'success';
}

$data = $model->getAll();

$pageTitle  = 'Permintaan Sarpras';
$activePage = 'permintaan_sarpras';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';

$badgeClass = [
    'diajukan'  => 'bg-warning text-dark',
    'disetujui' => 'bg-success',
    'ditolak'   => 'bg-danger',
    'selesai'   => 'bg-secondary',
];
$statusIcon = [
    'diajukan'  => 'bi-hourglass-split',
    'disetujui' => 'bi-check-circle',
    'ditolak'   => 'bi-x-circle',
    'selesai'   => 'bi-flag',
];
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
    .modal-nice .form-control:focus, .modal-nice .form-select:focus { box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
    .modal-nice .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; }
    .modal-nice .modal-footer .btn { border-radius: 10px; font-weight: 600; padding: .55rem 1.2rem; font-size: .82rem; }

    .modal-sarpras .modal-header { background: linear-gradient(135deg,#eef2ff,#f5f3ff); }
    .modal-sarpras .icon-badge { background: linear-gradient(135deg,#6366f1,#4f46e5); }
    .modal-sarpras .btn-save { background: linear-gradient(135deg,#6366f1,#4f46e5); border: 0; color: #fff; }
    .modal-sarpras .btn-save:hover { filter: brightness(0.95); color: #fff; }
    .modal-sarpras .form-control:focus, .modal-sarpras .form-select:focus { border-color: #6366f1; }
    .modal-sarpras .item-info {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: .85rem 1rem; margin-bottom: 1.1rem; display: flex; align-items: center; gap: .75rem;
    }
    .modal-sarpras .item-info .item-icon {
        width: 36px; height: 36px; border-radius: 10px; background: #eef2ff; color: #4f46e5;
        display: flex; align-items: center; justify-content: center; font-size: 1rem; flex: 0 0 auto;
    }
    .modal-sarpras .item-info .item-name { font-weight: 600; font-size: .85rem; color: #1a2332; }
    .modal-sarpras .item-info .item-meta { font-size: .74rem; color: #64748b; }
    /* Status: pakai pola resmi Bootstrap btn-check + label.btn, dipastikan kompatibel */
    .modal-sarpras .status-group { display: flex; flex-wrap: wrap; gap: .5rem; }
    .modal-sarpras .status-group .btn {
        border-radius: 999px !important; font-size: .78rem; font-weight: 600; padding: .4rem 1rem;
        border: 1.5px solid #e2e8f0; color: #64748b; background: #fff;
    }
    .modal-sarpras .status-group .btn-check:checked + .btn {
        background: #4f46e5; border-color: #4f46e5; color: #fff;
    }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Permintaan Sarana & Prasarana</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Pengajuan kebutuhan alat/sarana penunjang pemeliharaan RTH</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php
            $exportModul        = 'permintaan_sarpras';
            $exportLabel        = 'Permintaan Sarpras';
            $exportSupportsDate = true;
            require 'layouts/export_modal.php';
        ?>
        <?php if ($canCreate): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Ajukan Permintaan
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

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Daftar Permintaan <span class="badge bg-secondary ms-1"><?= count($data) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Terima Dari</th>
                        <th>Penerima</th>
                        <th>Nama Barang</th>
                        <th class="text-center">Jumlah</th>
                        <th>Alasan / Keperluan</th>
                        <th>Pemohon</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></td>
                            <td><?= htmlspecialchars($row['terima_dari'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['penerima'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td class="text-center"><?= (int) $row['jumlah'] ?> <?= htmlspecialchars($row['satuan']) ?></td>
                            <td style="max-width:200px;"><?= htmlspecialchars($row['alasan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['pemohon_nama'] ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $badgeClass[$row['status']] ?? 'bg-secondary' ?>">
                                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <?php if ($canEdit): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal" data-bs-target="#modalTanggapi<?= (int) $row['id'] ?>">
                                    <i class="bi bi-chat-left-text"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                <a href="permintaan_sarpras.php?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus permintaan ini?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted py-5">Belum ada permintaan sarpras</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<?php foreach ($data as $row): ?>
<!-- Modal Tanggapi -->
<div class="modal fade modal-nice modal-sarpras" id="modalTanggapi<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="icon-badge"><i class="bi bi-chat-left-text"></i></span>
                    <span>
                        <span class="title-text d-block">Tanggapi Permintaan</span>
                        <span class="modal-subtitle d-block">Perbarui status &amp; catatan untuk permintaan ini</span>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="item-info">
                    <div class="item-icon"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <div class="item-name"><?= htmlspecialchars($row['nama_barang']) ?></div>
                        <div class="item-meta"><?= (int) $row['jumlah'] ?> <?= htmlspecialchars($row['satuan']) ?> &middot; diajukan oleh <?= htmlspecialchars($row['pemohon_nama'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <div class="status-group">
                        <?php foreach (['diajukan', 'disetujui', 'ditolak', 'selesai'] as $s): ?>
                        <input type="radio" class="btn-check" name="status" id="status<?= $s ?>_<?= (int) $row['id'] ?>" value="<?= $s ?>" autocomplete="off" <?= $row['status'] === $s ? 'checked' : '' ?> required>
                        <label class="btn" for="status<?= $s ?>_<?= (int) $row['id'] ?>"><i class="bi <?= $statusIcon[$s] ?> me-1"></i><?= ucfirst($s) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" name="catatan_admin" rows="2" placeholder="Opsional — alasan/keterangan untuk pemohon"><?= htmlspecialchars($row['catatan_admin'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tanggapi" class="btn btn-save"><i class="bi bi-check2-circle me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tools text-success me-2"></i>Ajukan Permintaan Sarpras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Terima Dari <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="terima_dari" placeholder="mis. Gudang Dinas" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Penerima <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="penerima" placeholder="Nama petugas penerima" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama_barang" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Jumlah</label>
                        <input type="number" class="form-control" name="jumlah" value="1" min="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" name="satuan" value="unit">
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">Alasan / Kebutuhan</label>
                    <textarea class="form-control" name="alasan" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add" class="btn btn-success">Ajukan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>