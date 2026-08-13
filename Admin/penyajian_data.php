<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'penyajian_data', 'view');
$canCreate = Rbac::can($config, 'penyajian_data', 'create');
$canDelete = Rbac::can($config, 'penyajian_data', 'delete');
require_once 'core/PenyajianDataModel.php';

$model     = new PenyajianDataModel($config);
$alertMsg  = '';
$alertType = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!$canCreate) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menambah data penyajian.';
        $alertType = 'warning';
    } else {
        $jenis = ($_POST['jenis'] ?? '') === 'rth_persen' ? 'rth_persen' : 'iktl';
        $ok = $model->upsert(
            $jenis,
            (int) ($_POST['tahun'] ?? date('Y')),
            (float) ($_POST['nilai'] ?? 0),
            trim($_POST['keterangan'] ?? ''),
            $currentUserId
        );
        $alertMsg  = $ok ? 'Data berhasil disimpan/diperbarui.' : 'Gagal menyimpan data.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

if (isset($_GET['hapus'])) {
    if ($canDelete) { $model->delete((int) $_GET['hapus']); }
    header('Location: penyajian_data.php');
    exit;
}

$iktlData = $model->getByJenis('iktl');
$rthData  = $model->getByJenis('rth_persen');

$pageTitle  = 'Penyajian Data';
$activePage = 'penyajian_data';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Penyajian Data</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Nilai IKTL dan Persentase RTH per tahun (input manual)</p>
    </div>
    <?php if ($canCreate): ?>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Input Data Tahunan
    </button>
    <?php endif; ?>
</div>

<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-graph-up me-2"></i>Nilai IKTL per Tahun</div>
            <div class="card-body">
                <canvas id="chartIktl" height="180"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th class="ps-3">Tahun</th><th>Nilai IKTL</th><th>Keterangan</th><th class="text-center pe-3">Aksi</th></tr></thead>
                    <tbody>
                    <?php if ($iktlData): foreach ($iktlData as $row): ?>
                        <tr>
                            <td class="ps-3"><?= (int) $row['tahun'] ?></td>
                            <td><?= number_format((float) $row['nilai'], 2, ',', '.') ?></td>
                            <td class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                            <td class="text-center pe-3">
                                <?php if ($canDelete): ?><a href="?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data IKTL</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Persentase RTH per Tahun</div>
            <div class="card-body">
                <canvas id="chartRth" height="180"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th class="ps-3">Tahun</th><th>% RTH</th><th>Keterangan</th><th class="text-center pe-3">Aksi</th></tr></thead>
                    <tbody>
                    <?php if ($rthData): foreach ($rthData as $row): ?>
                        <tr>
                            <td class="ps-3"><?= (int) $row['tahun'] ?></td>
                            <td><?= number_format((float) $row['nilai'], 2, ',', '.') ?>%</td>
                            <td class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                            <td class="text-center pe-3">
                                <?php if ($canDelete): ?><a href="?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data persentase RTH</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-bar-chart-line text-success me-2"></i>Input Data Tahunan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Jenis Data <span class="text-danger">*</span></label>
                    <select class="form-select" name="jenis" required>
                        <option value="iktl">Nilai IKTL</option>
                        <option value="rth_persen">Persentase RTH</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Tahun <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="tahun" value="<?= date('Y') ?>" min="2000" max="2100" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Nilai <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="nilai" required>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="keterangan" rows="2" placeholder="Sumber data / catatan"></textarea>
                </div>
                <small class="text-muted">Jika tahun sudah pernah diinput untuk jenis yang sama, nilainya akan diperbarui (bukan duplikat).</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const iktlLabels = <?= json_encode(array_map(fn($r) => (int) $r['tahun'], $iktlData)) ?>;
const iktlValues = <?= json_encode(array_map(fn($r) => (float) $r['nilai'], $iktlData)) ?>;
const rthLabels  = <?= json_encode(array_map(fn($r) => (int) $r['tahun'], $rthData)) ?>;
const rthValues  = <?= json_encode(array_map(fn($r) => (float) $r['nilai'], $rthData)) ?>;

new Chart(document.getElementById('chartIktl'), {
    type: 'line',
    data: { labels: iktlLabels, datasets: [{ label: 'Nilai IKTL', data: iktlValues, borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.15)', fill: true, tension: 0.25 }] },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chartRth'), {
    type: 'bar',
    data: { labels: rthLabels, datasets: [{ label: '% RTH', data: rthValues, backgroundColor: '#22c55e' }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
});
</script>

<?php require_once 'layouts/footer.php'; ?>
