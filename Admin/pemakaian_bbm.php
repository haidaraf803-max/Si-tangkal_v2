<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'pemakaian_bbm', 'view');
$canCreate = Rbac::can($config, 'pemakaian_bbm', 'create');
$canEdit   = Rbac::can($config, 'pemakaian_bbm', 'edit');
$canDelete = Rbac::can($config, 'pemakaian_bbm', 'delete');
require_once 'core/PemeliharaanModels.php';

$model     = new BbmModel($config);
$alertMsg  = '';
$alertType = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!$canCreate) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menambah data pemakaian BBM.';
        $alertType = 'warning';
    } else {
        $bukti = null;
        if (!empty($_FILES['bukti']['name'])) {
            $bukti = 'bbm_' . time() . '_' . basename($_FILES['bukti']['name']);
            move_uploaded_file($_FILES['bukti']['tmp_name'], __DIR__ . '/../assets/foto/' . $bukti);
        }
        $ok = $model->create([
            'tanggal'        => $_POST['tanggal'] ?? date('Y-m-d'),
            'terima_dari'    => trim($_POST['terima_dari'] ?? ''),
            'penerima'       => trim($_POST['penerima'] ?? ''),
            'keperluan'      => trim($_POST['keperluan'] ?? ''),
            'jumlah_kupon'   => trim($_POST['jumlah_kupon'] ?? ''),
            'nominal_kupon'  => trim($_POST['nominal_kupon'] ?? ''),
            'jenis_bbm'      => trim($_POST['jenis_bbm'] ?? ''),
            'jumlah_liter'   => trim($_POST['jumlah_liter'] ?? ''),
            'nominal_rupiah' => (float) ($_POST['nominal_rupiah'] ?? 0),
            'kendaraan'      => trim($_POST['kendaraan'] ?? ''),
            'keterangan'     => trim($_POST['keterangan'] ?? ''),
            'bukti'          => $bukti,
        ], $currentUserId);
        $alertMsg  = $ok ? 'Data pemakaian BBM berhasil ditambahkan.' : 'Gagal menambahkan data.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    if (!$canEdit) {
        $alertMsg  = 'Peran Anda tidak memiliki izin mengubah data pemakaian BBM.';
        $alertType = 'warning';
    } else {
        $ok = $model->update((int) $_POST['id'], [
            'tanggal'       => $_POST['tanggal'] ?? date('Y-m-d'),
            'terima_dari'   => trim($_POST['terima_dari'] ?? ''),
            'penerima'      => trim($_POST['penerima'] ?? ''),
            'keperluan'     => trim($_POST['keperluan'] ?? ''),
            'jumlah_kupon'  => trim($_POST['jumlah_kupon'] ?? ''),
            'nominal_kupon' => trim($_POST['nominal_kupon'] ?? ''),
            'keterangan'    => trim($_POST['keterangan'] ?? ''),
        ]);
        $alertMsg  = $ok ? 'Data pemakaian BBM berhasil diperbarui.' : 'Gagal memperbarui data.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

if (isset($_GET['hapus'])) {
    if (!$canDelete) { header('Location: pemakaian_bbm.php?deleted=forbidden'); exit; }
    $model->delete((int) $_GET['hapus']);
    header('Location: pemakaian_bbm.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] === 'forbidden' ? 'Peran Anda tidak memiliki izin menghapus data.' : 'Data berhasil dihapus.';
    $alertType = $_GET['deleted'] === 'forbidden' ? 'warning' : 'success';
}

$data = $model->getAll();
$totalKuponBulanIni   = 0;
$totalNominalBulanIni = 0;
foreach ($data as $row) {
    if (date('Y-m', strtotime($row['tanggal'])) === date('Y-m')) {
        $totalKuponBulanIni   += (int) ($row['jumlah_kupon'] ?? 0);
        $totalNominalBulanIni += (float) ($row['nominal_kupon'] ?? $row['nominal_rupiah'] ?? 0);
    }
}

$pageTitle  = 'Pemakaian BBM';
$activePage = 'pemakaian_bbm';
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

    .modal-bbm .modal-header { background: linear-gradient(135deg,#fff7ed,#fffbeb); }
    .modal-bbm .icon-badge { background: linear-gradient(135deg,#f59e0b,#ea580c); }
    .modal-bbm .btn-save { background: linear-gradient(135deg,#f59e0b,#ea580c); border: 0; color: #fff; }
    .modal-bbm .btn-save:hover { filter: brightness(0.95); color: #fff; }
    .modal-bbm .form-control:focus, .modal-bbm .form-select:focus { border-color: #f59e0b; }

    /* ===== Modal Detail (read-only) ===== */
    .modal-detail .detail-row { display: flex; justify-content: space-between; gap: 1rem; padding: .55rem 0; border-bottom: 1px solid #f1f5f9; }
    .modal-detail .detail-row:last-child { border-bottom: 0; }
    .modal-detail .detail-label { font-size: .74rem; font-weight: 600; letter-spacing: .3px; text-transform: uppercase; color: #94a3b8; flex: 0 0 42%; }
    .modal-detail .detail-value { font-size: .875rem; color: #1a2332; font-weight: 500; text-align: right; flex: 1; word-break: break-word; }
    .modal-detail .detail-photo { width: 100%; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: .5rem; }
    .modal-detail .detail-photo-empty { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1.5rem; text-align: center; color: #94a3b8; font-size: .8rem; margin-top: .5rem; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Pemakaian BBM</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Catatan penerimaan &amp; pemakaian kupon BBM operasional</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php
            $exportModul        = 'pemakaian_bbm';
            $exportLabel        = 'Pemakaian BBM';
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
            <div class="text-muted" style="font-size:0.75rem;">Jumlah Kupon Bulan Ini</div>
            <div class="fs-4 fw-bold"><?= number_format($totalKuponBulanIni, 0, ',', '.') ?> lembar</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.75rem;">Total Nominal Bulan Ini</div>
            <div class="fs-4 fw-bold">Rp <?= number_format($totalNominalBulanIni, 0, ',', '.') ?></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Riwayat Pemakaian BBM</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Terima Dari</th>
                        <th>Penerima</th>
                        <th>Keperluan</th>
                        <th class="text-center">Jumlah Kupon</th>
                        <th class="text-end">Nominal Kupon</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></td>
                            <td><?= htmlspecialchars($row['terima_dari'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['penerima'] ?? ($row['petugas_nama'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars($row['keperluan'] ?? '-') ?></td>
                            <td class="text-center"><?= $row['jumlah_kupon'] !== null ? (int) $row['jumlah_kupon'] . ' lbr' : '-' ?></td>
                            <td class="text-end">Rp <?= number_format((float) ($row['nominal_kupon'] ?? $row['nominal_rupiah'] ?? 0), 0, ',', '.') ?></td>
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
                                <a href="pemakaian_bbm.php?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus catatan ini?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">Belum ada data pemakaian BBM</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($data as $row): ?>
<!-- Modal Detail -->
<div class="modal fade modal-nice modal-bbm modal-detail" id="modalDetail<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="icon-badge"><i class="bi bi-fuel-pump"></i></span>
                    <span>
                        <span class="title-text d-block">Detail Pemakaian BBM</span>
                        <span class="modal-subtitle d-block"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></span>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="detail-row"><span class="detail-label">Tanggal</span><span class="detail-value"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></span></div>
                <div class="detail-row"><span class="detail-label">Terima Dari</span><span class="detail-value"><?= htmlspecialchars($row['terima_dari'] ?? '—') ?></span></div>
                <div class="detail-row"><span class="detail-label">Penerima</span><span class="detail-value"><?= htmlspecialchars($row['penerima'] ?? ($row['petugas_nama'] ?? '—')) ?></span></div>
                <div class="detail-row"><span class="detail-label">Keperluan</span><span class="detail-value"><?= htmlspecialchars($row['keperluan'] ?? '—') ?></span></div>
                <div class="detail-row"><span class="detail-label">Jumlah Kupon</span><span class="detail-value"><?= $row['jumlah_kupon'] !== null ? (int) $row['jumlah_kupon'] . ' lembar' : '—' ?></span></div>
                <div class="detail-row"><span class="detail-label">Nominal Kupon</span><span class="detail-value">Rp <?= number_format((float) ($row['nominal_kupon'] ?? $row['nominal_rupiah'] ?? 0), 0, ',', '.') ?></span></div>
                <?php if (!empty($row['jenis_bbm'])): ?>
                <div class="detail-row"><span class="detail-label">Jenis BBM</span><span class="detail-value"><?= htmlspecialchars($row['jenis_bbm']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($row['jumlah_liter'])): ?>
                <div class="detail-row"><span class="detail-label">Jumlah Liter</span><span class="detail-value"><?= htmlspecialchars($row['jumlah_liter']) ?> liter</span></div>
                <?php endif; ?>
                <?php if (!empty($row['kendaraan'])): ?>
                <div class="detail-row"><span class="detail-label">Kendaraan</span><span class="detail-value"><?= htmlspecialchars($row['kendaraan']) ?></span></div>
                <?php endif; ?>
                <div class="detail-row"><span class="detail-label">Keterangan</span><span class="detail-value"><?= $row['keterangan'] ? nl2br(htmlspecialchars($row['keterangan'])) : '—' ?></span></div>
                <?php if (!empty($row['bukti'])): ?>
                    <img src="../assets/foto/<?= htmlspecialchars($row['bukti']) ?>" class="detail-photo" alt="Bukti struk/kupon BBM">
                <?php else: ?>
                    <div class="detail-photo-empty"><i class="bi bi-image me-1"></i>Tidak ada bukti/foto</div>
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
<div class="modal fade modal-nice modal-bbm" id="modalEdit<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="icon-badge"><i class="bi bi-fuel-pump"></i></span>
                    <span>
                        <span class="title-text d-block">Edit Pemakaian BBM</span>
                        <span class="modal-subtitle d-block">Perbarui detail penerimaan &amp; pemakaian kupon</span>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        <input type="date" class="form-control" name="tanggal" value="<?= htmlspecialchars(date('Y-m-d', strtotime($row['tanggal']))) ?>" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Terima Dari <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="terima_dari" value="<?= htmlspecialchars($row['terima_dari'] ?? '') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Penerima <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="penerima" value="<?= htmlspecialchars($row['penerima'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keperluan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="keperluan" value="<?= htmlspecialchars($row['keperluan'] ?? '') ?>" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Jumlah Kupon <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="1" min="1" class="form-control" name="jumlah_kupon" value="<?= htmlspecialchars($row['jumlah_kupon'] ?? '') ?>" required>
                            <span class="input-group-text" style="border-radius:0 10px 10px 0;border-left:0;">lbr</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Nominal Kupon <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" step="1" class="form-control" name="nominal_kupon" value="<?= htmlspecialchars($row['nominal_kupon'] ?? '') ?>" required>
                        </div>
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

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-fuel-pump text-success me-2"></i>Catat Pemakaian BBM</h5>
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
                        <input type="text" class="form-control" name="terima_dari" placeholder="mis. Bendahara/SPBU" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Penerima <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="penerima" placeholder="Nama petugas penerima" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keperluan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="keperluan" placeholder="mis. Operasional kendaraan pemangkasan" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Jumlah Kupon <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="1" class="form-control" name="jumlah_kupon" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Nominal Kupon (Rp) <span class="text-danger">*</span></label>
                        <input type="number" step="1" class="form-control" name="nominal_kupon" required>
                    </div>
                </div>
                <details class="mb-3">
                    <summary class="text-muted" style="font-size:0.8rem; cursor:pointer;">Detail tambahan (opsional)</summary>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <label class="form-label">Jenis BBM</label>
                            <select class="form-select" name="jenis_bbm">
                                <option value="">-</option>
                                <option value="Pertalite">Pertalite</option>
                                <option value="Pertamax">Pertamax</option>
                                <option value="Solar">Solar</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kendaraan</label>
                            <input type="text" class="form-control" name="kendaraan" placeholder="Plat nomor">
                        </div>
                    </div>
                </details>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="keterangan" rows="2"></textarea>
                </div>
                <div class="mb-1">
                    <label class="form-label">Bukti (struk/kupon)</label>
                    <input type="file" class="form-control" name="bukti" accept="image/*">
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