<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/MonitoringModel.php';

$model        = new MonitoringModel($config);
$statusUpdate = null;
$errMsg       = '';

if (!isset($_GET['id'])) {
    header("Location: monitoring.php");
    exit;
}

$id   = (int) $_GET['id'];
$data = $model->getById($id);

if (!$data) {
    header("Location: monitoring.php");
    exit;
}

// ===== PROSES UPDATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pohon_id             = (int) ($_POST['pohon_id'] ?? 0);
    $tanggal_monitoring   = trim($_POST['tanggal_monitoring'] ?? '');
    $kesehatan_monitoring = trim($_POST['kesehatan_monitoring'] ?? '');
    $catatan              = trim($_POST['catatan'] ?? '');
    $deleteMediaIds       = $_POST['delete_media'] ?? [];
    $files                = $_FILES['files'] ?? [];

    if ($pohon_id > 0) {
        $result = $model->update($id, $pohon_id, $tanggal_monitoring, $kesehatan_monitoring, $catatan, $files, $deleteMediaIds);
        $statusUpdate = $result['success'] ? 'success' : 'error';
        $errMsg = $result['message'];
        if ($result['success']) {
            $data = $model->getById($id); // refresh
        }
    } else {
        $statusUpdate = 'error_validation';
        $errMsg = 'Pohon wajib dipilih.';
    }
}

$pohonList = $model->getPohonOptions();

$pageTitle  = 'Edit Monitoring';
$activePage = 'monitoring';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:0.8rem;">
        <li class="breadcrumb-item"><a href="index.php" class="text-success">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="monitoring.php" class="text-success">Monitoring</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">

        <?php if ($statusUpdate === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>Data monitoring berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif ($statusUpdate === 'error' || $statusUpdate === 'error_validation'): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($errMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-warning"></i>
                <strong>Edit Data Monitoring</strong>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label" for="pohon_id">Pohon <span class="text-danger">*</span></label>
                            <select id="pohon_id" name="pohon_id" class="form-select" required>
                                <option value="">-- Pilih Pohon --</option>
                                <?php foreach ($pohonList as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= ((int) $data['pohon_id'] === (int) $p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nama_lokal']) ?> — <?= htmlspecialchars($p['nama_jalan']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tanggal_monitoring">Tanggal Monitoring</label>
                            <input type="date" id="tanggal_monitoring" name="tanggal_monitoring" class="form-control"
                                   value="<?= htmlspecialchars(date('Y-m-d', strtotime($data['tanggal_monitoring']))) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="kesehatan_monitoring">Kondisi Kesehatan</label>
                            <select id="kesehatan_monitoring" name="kesehatan_monitoring" class="form-select">
                                <option value="">-- Pilih Kondisi --</option>
                                <?php foreach (['Sehat', 'Kurang Sehat', 'Sakit'] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($data['kesehatan_monitoring'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Petugas</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['petugas_name'] ?? '—') ?>" disabled>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="catatan">Catatan</label>
                            <textarea id="catatan" name="catatan" class="form-control" rows="3"><?= htmlspecialchars($data['catatan'] ?? '') ?></textarea>
                        </div>

                        <!-- Media lama -->
                        <div class="col-12">
                            <label class="form-label">Media Saat Ini</label>
                            <?php if (!empty($data['media'])): ?>
                            <div class="row g-2">
                                <?php foreach ($data['media'] as $m): ?>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-2 p-2 h-100 d-flex flex-column">
                                        <?php if ($m['media_type'] === 'video'): ?>
                                        <video src="../<?= htmlspecialchars($m['file_path']) ?>" class="w-100 rounded-1 mb-2" style="height:100px; object-fit:cover;" muted></video>
                                        <?php else: ?>
                                        <img src="../<?= htmlspecialchars($m['file_path']) ?>" class="w-100 rounded-1 mb-2" style="height:100px; object-fit:cover;" alt="media">
                                        <?php endif; ?>
                                        <div class="form-check mt-auto">
                                            <input class="form-check-input" type="checkbox" name="delete_media[]" value="<?= (int) $m['id'] ?>" id="del_<?= (int) $m['id'] ?>">
                                            <label class="form-check-label text-danger" style="font-size:0.72rem;" for="del_<?= (int) $m['id'] ?>">
                                                Hapus file ini
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-muted mb-0" style="font-size:0.8rem;">Belum ada media untuk monitoring ini.</p>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="files">Tambah Foto / Video Baru</label>
                            <input type="file" id="files" name="files[]" class="form-control"
                                   accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi,.mkv" multiple>
                            <div class="form-text">File baru akan ditambahkan ke media yang sudah ada (boleh pilih lebih dari satu).</div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--border-color);">
                    <a href="monitoring.php" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-warning text-white"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
