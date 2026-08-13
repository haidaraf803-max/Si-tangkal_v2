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
