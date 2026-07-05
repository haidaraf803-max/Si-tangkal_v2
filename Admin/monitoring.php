<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/MonitoringModel.php';

$model     = new MonitoringModel($config);
$alertMsg  = '';
$alertType = '';

$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    $hapusId = (int) $_GET['hapus'];
    $result  = $model->delete($hapusId);
    header("Location: monitoring.php?deleted=" . ($result['success'] ? '1' : '0'));
    exit;
}

if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] == '1' ? 'Data monitoring berhasil dihapus.' : 'Gagal menghapus data monitoring.';
    $alertType = $_GET['deleted'] == '1' ? 'success' : 'danger';
}

// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {

    $pohon_id             = (int) ($_POST['pohon_id'] ?? 0);
    $tanggal_monitoring   = trim($_POST['tanggal_monitoring'] ?? '');
    $kesehatan_monitoring = trim($_POST['kesehatan_monitoring'] ?? '');
    $catatan              = trim($_POST['catatan'] ?? '');
    $files                = $_FILES['files'] ?? [];

    if ($pohon_id > 0) {
        $result    = $model->create($pohon_id, $currentUserId, $tanggal_monitoring, $kesehatan_monitoring, $catatan, $files);
        $alertMsg  = $result['message'];
        $alertType = $result['success'] ? 'success' : 'danger';
    } else {
        $alertMsg  = 'Pohon wajib dipilih.';
        $alertType = 'warning';
    }
}

// ===== SEARCH / GET ALL =====
$keyword = trim($_GET['cari'] ?? '');
$data    = $model->getAll($keyword);

$totalMonitoring = $model->countAll();
$totalSehat      = $model->countByKesehatan('Sehat');
$totalKurang     = $model->countByKesehatan('Kurang Sehat');
$totalSakit      = $model->countByKesehatan('Sakit');

$pohonList = $model->getPohonOptions();

$pageTitle  = 'Monitoring Pohon';
$activePage = 'monitoring';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Monitoring Pohon</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Kelola riwayat monitoring kondisi pohon beserta dokumentasi foto/video</p>
    </div>
    <div>
        <!-- <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Monitoring
        </button> -->
    </div>
</div>

<!-- Mini Stat Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-clipboard-data text-primary fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalMonitoring ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Total Monitoring</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
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
    <div class="col-6 col-md-3">
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
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalSakit ?></div>
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

<!-- ======= TABLE ======= -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-table me-2"></i>Data Monitoring
            <span class="badge bg-secondary ms-1"><?= count($data) ?></span>
        </span>
        <form method="GET" class="d-flex gap-2" style="min-width:240px;">
            <input type="text" name="cari" class="form-control form-control-sm"
                   placeholder="Cari nama pohon / lokasi / catatan..."
                   value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-search"></i></button>
            <?php if ($keyword): ?>
            <a href="monitoring.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No</th>
                        <th>Tanggal</th>
                        <th>Pohon</th>
                        <th>Lokasi</th>
                        <th class="text-center">Kesehatan</th>
                        <th>Petugas</th>
                        <th class="text-center">Media</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php $no = 1; foreach ($data as $row):
                            $badgeClass = match ($row['kesehatan_monitoring']) {
                                'Sehat'        => 'bg-success-subtle text-success',
                                'Kurang Sehat' => 'bg-warning-subtle text-warning',
                                'Sakit'        => 'bg-danger-subtle text-danger',
                                default        => 'bg-secondary-subtle text-secondary',
                            };
                        ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                            <td class="text-nowrap"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal_monitoring']))) ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['nama_lokal'] ?? '—') ?></td>
                            <td>
                                <span style="max-width:180px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                      title="<?= htmlspecialchars($row['nama_jalan'] ?? '') ?>">
                                    <?= htmlspecialchars($row['nama_jalan'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($row['kesehatan_monitoring'] ?: '—') ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['petugas_name'] ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info">
                                    <i class="bi bi-images me-1"></i><?= (int) $row['media_count'] ?>
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <a href="detail_monitoring.php?id=<?= (int) $row['id'] ?>"
                                   class="btn btn-sm btn-outline-info" title="Detail">
                                    <i class="bi bi-info"></i>
                                </a>
                                <a href="edit_monitoring.php?id=<?= (int) $row['id'] ?>"
                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="monitoring.php?hapus=<?= (int) $row['id'] ?>"
                                   class="btn btn-sm btn-outline-danger" title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus data monitoring ini beserta seluruh medianya?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-clipboard-data fs-2 d-block mb-2"></i>
                                <?= $keyword ? "Tidak ditemukan data untuk \"<strong>" . htmlspecialchars($keyword) . "</strong>\"" : 'Belum ada data monitoring' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======= MODAL: TAMBAH MONITORING ======= -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle text-success me-2"></i>Tambah Monitoring Pohon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label" for="pohon_id">Pohon <span class="text-danger">*</span></label>
                            <select id="pohon_id" name="pohon_id" class="form-select" required>
                                <option value="">-- Pilih Pohon --</option>
                                <?php foreach ($pohonList as $p): ?>
                                <option value="<?= (int) $p['id'] ?>">
                                    <?= htmlspecialchars($p['nama_lokal']) ?> — <?= htmlspecialchars($p['nama_jalan']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tanggal_monitoring">Tanggal Monitoring</label>
                            <input type="date" id="tanggal_monitoring" name="tanggal_monitoring" class="form-control"
                                   value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="kesehatan_monitoring">Kondisi Kesehatan</label>
                            <select id="kesehatan_monitoring" name="kesehatan_monitoring" class="form-select">
                                <option value="">-- Pilih Kondisi --</option>
                                <option value="Sehat">Sehat</option>
                                <option value="Kurang Sehat">Kurang Sehat</option>
                                <option value="Sakit">Sakit</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="catatan">Catatan</label>
                            <textarea id="catatan" name="catatan" class="form-control" rows="3"
                                      placeholder="Catatan hasil monitoring (opsional)"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="files">Foto / Video Dokumentasi</label>
                            <input type="file" id="files" name="files[]" class="form-control"
                                   accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi,.mkv" multiple>
                            <div class="form-text">Boleh memilih lebih dari satu file (foto dan/atau video) sekaligus.</div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
