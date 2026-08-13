<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'pergantian_pohon', 'view');
$canCreate = Rbac::can($config, 'pergantian_pohon', 'create');
$canEdit   = Rbac::can($config, 'pergantian_pohon', 'edit'); // dipakai utk kelola tarif
$canDelete = Rbac::can($config, 'pergantian_pohon', 'delete');
require_once 'core/PergantianPohonModel.php';

$model      = new PergantianModel($config);
$tarifModel = new TarifModel($config);
$alertMsg   = '';
$alertType  = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== HITUNG & SIMPAN PERGANTIAN POHON =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!$canCreate) {
        $alertMsg  = 'Peran Anda tidak memiliki izin membuat perhitungan pergantian pohon.';
        $alertType = 'warning';
    } else {
        $newId = $model->create([
            'jenis_pohon'   => trim($_POST['jenis_pohon'] ?? ''),
            'diameter_cm'   => (float) ($_POST['diameter_cm'] ?? 0),
            'jumlah_pohon'  => (int) ($_POST['jumlah_pohon'] ?? 1),
            'nomor_surat'   => trim($_POST['nomor_surat'] ?? ''),
            'tanggal_surat' => $_POST['tanggal_surat'] ?? date('Y-m-d'),
            'nama_kabid'    => trim($_POST['nama_kabid'] ?? ''),
            'nip_kabid'     => trim($_POST['nip_kabid'] ?? ''),
        ], $currentUserId);

        if ($newId) {
            header("Location: surat_pergantian.php?id={$newId}");
            exit;
        }
        $alertMsg  = 'Gagal menyimpan perhitungan pergantian pohon.';
        $alertType = 'danger';
    }
}

// ===== KELOLA TARIF =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tarif'])) {
    if (!$canEdit) {
        $alertMsg  = 'Peran Anda tidak memiliki izin mengubah tarif.';
        $alertType = 'warning';
    } else {
        $tarifModel->create(
            trim($_POST['t_jenis'] ?? ''),
            (float) ($_POST['t_min'] ?? 0),
            (float) ($_POST['t_max'] ?? 999),
            (float) ($_POST['t_harga'] ?? 0),
            trim($_POST['t_ket'] ?? '')
        );
        $alertMsg  = 'Tarif baru berhasil ditambahkan.';
        $alertType = 'success';
    }
}

if (isset($_GET['hapus_tarif']) && $canEdit) {
    $tarifModel->delete((int) $_GET['hapus_tarif']);
    header('Location: pergantian_pohon.php?deleted_tarif=1');
    exit;
}

if (isset($_GET['hapus'])) {
    if (!$canDelete) { header('Location: pergantian_pohon.php?deleted=forbidden'); exit; }
    $model->delete((int) $_GET['hapus']);
    header('Location: pergantian_pohon.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] === 'forbidden' ? 'Peran Anda tidak memiliki izin menghapus data.' : 'Data berhasil dihapus.';
    $alertType = $_GET['deleted'] === 'forbidden' ? 'warning' : 'success';
}
if (isset($_GET['deleted_tarif'])) {
    $alertMsg  = 'Tarif berhasil dihapus.';
    $alertType = 'success';
}

$data       = $model->getAll();
$daftarTarif = $tarifModel->getAll();
$totalKeseluruhan = array_sum(array_column($data, 'total_biaya'));

$pageTitle  = 'Pergantian Pohon';
$activePage = 'pergantian_pohon';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Perhitungan Pergantian Pohon</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">
            Hitung biaya pergantian pohon yang ditebang & buat surat resminya.
            Formula: <code>jumlah pohon &times; diameter (cm) &times; harga per cm</code>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php
            $exportModul        = 'pergantian_pohon';
            $exportLabel        = 'Pergantian Pohon';
            $exportSupportsDate = true;
            require 'layouts/export_modal.php';
        ?>
        <?php if ($canEdit): ?>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalTarif">
            <i class="bi bi-tags me-1"></i> Kelola Tarif
        </button>
        <?php endif; ?>
        <?php if ($canCreate): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalHitung">
            <i class="bi bi-calculator me-1"></i> Hitung Pergantian
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
            <div class="text-muted" style="font-size:0.75rem;">Total Perhitungan</div>
            <div class="fs-4 fw-bold"><?= count($data) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.75rem;">Total Nilai Pergantian</div>
            <div class="fs-4 fw-bold">Rp <?= number_format($totalKeseluruhan, 0, ',', '.') ?></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-receipt me-2"></i>Riwayat Perhitungan</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">No Surat</th>
                        <th>Jenis Pohon</th>
                        <th class="text-center">Diameter</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-end">Total Biaya</th>
                        <th>Dibuat Oleh</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars($row['nomor_surat'] ?: '(belum diisi)') ?></td>
                            <td><?= htmlspecialchars($row['jenis_pohon']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['diameter_cm']) ?> cm</td>
                            <td class="text-center"><?= (int) $row['jumlah_pohon'] ?></td>
                            <td class="text-end fw-600">Rp <?= number_format((float) $row['total_biaya'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['dibuat_oleh_nama'] ?? '—') ?></td>
                            <td class="text-center pe-3">
                                <a href="surat_pergantian.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Lihat/Cetak Surat">
                                    <i class="bi bi-file-earmark-text"></i>
                                </a>
                                <?php if ($canDelete): ?>
                                <a href="pergantian_pohon.php?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus perhitungan ini?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">Belum ada perhitungan pergantian pohon</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Hitung Pergantian -->
<div class="modal fade" id="modalHitung" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calculator text-success me-2"></i>Hitung Pergantian Pohon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Jenis Pohon <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="jenis_pohon" list="listJenisPohon" placeholder="Ketik atau pilih dari tarif yang ada" required>
                    <datalist id="listJenisPohon">
                        <?php foreach ($daftarTarif as $t): ?>
                        <option value="<?= htmlspecialchars($t['jenis_pohon']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text">Jika jenis tidak ditemukan di tarif, sistem otomatis memakai tarif "Umum".</div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Diameter (cm) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" class="form-control" name="diameter_cm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Jumlah Pohon <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="jumlah_pohon" value="1" min="1" required>
                    </div>
                </div>
                <hr>
                <p class="text-muted mb-2" style="font-size:0.8rem;">Data untuk surat resmi (bisa dilengkapi nanti):</p>
                <div class="mb-3">
                    <label class="form-label">Nomor Surat</label>
                    <input type="text" class="form-control" name="nomor_surat">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Surat</label>
                    <input type="date" class="form-control" name="tanggal_surat" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="row g-2 mb-1">
                    <div class="col-6">
                        <label class="form-label">Nama Kabid</label>
                        <input type="text" class="form-control" name="nama_kabid">
                    </div>
                    <div class="col-6">
                        <label class="form-label">NIP Kabid</label>
                        <input type="text" class="form-control" name="nip_kabid">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add" class="btn btn-success"><i class="bi bi-calculator me-1"></i> Hitung & Buat Surat</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Kelola Tarif -->
<div class="modal fade" id="modalTarif" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tags text-success me-2"></i>Kelola Tarif Pergantian Pohon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr><th>Jenis Pohon</th><th>Diameter (cm)</th><th class="text-end">Harga / cm</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftarTarif as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['jenis_pohon']) ?></td>
                                <td><?= htmlspecialchars($t['diameter_min']) ?> &ndash; <?= htmlspecialchars($t['diameter_max']) ?></td>
                                <td class="text-end">Rp <?= number_format((float) $t['harga_per_cm'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <a href="pergantian_pohon.php?hapus_tarif=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Hapus tarif ini?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <hr>
                <form method="POST">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Jenis Pohon</label>
                            <input type="text" class="form-control" name="t_jenis" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Diameter Min</label>
                            <input type="number" step="0.1" class="form-control" name="t_min" value="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Diameter Max</label>
                            <input type="number" step="0.1" class="form-control" name="t_max" value="999" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Harga/cm</label>
                            <input type="number" step="1" class="form-control" name="t_harga" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="add_tarif" class="btn btn-success w-100">Tambah</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
