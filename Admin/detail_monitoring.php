<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/MonitoringModel.php';

$model = new MonitoringModel($config);

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

$badgeClass = match ($data['kesehatan_monitoring']) {
    'Sehat'        => 'bg-success-subtle text-success',
    'Kurang Sehat' => 'bg-warning-subtle text-warning',
    'Sakit'        => 'bg-danger-subtle text-danger',
    default        => 'bg-secondary-subtle text-secondary',
};

$fotoMedia  = array_values(array_filter($data['media'], fn($m) => $m['media_type'] === 'foto'));
$videoMedia = array_values(array_filter($data['media'], fn($m) => $m['media_type'] === 'video'));

$pageTitle  = 'Detail Monitoring';
$activePage = 'monitoring';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:0.8rem;">
        <li class="breadcrumb-item"><a href="index.php" class="text-success">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="monitoring.php" class="text-success">Monitoring</a></li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">

        <!-- Data Monitoring -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-clipboard-data text-primary"></i>
                <strong>Data Monitoring</strong>
                <span class="ms-auto">
                    <span class="badge rounded-pill <?= $badgeClass ?>">
                        <?= htmlspecialchars($data['kesehatan_monitoring'] ?: 'Tidak diketahui') ?>
                    </span>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Tanggal Monitoring</div>
                        <div class="fw-500"><?= htmlspecialchars(date('d F Y', strtotime($data['tanggal_monitoring']))) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Petugas</div>
                        <div class="fw-500"><?= htmlspecialchars($data['petugas_name'] ?? '—') ?> <span class="text-muted">(<?= htmlspecialchars($data['petugas_username'] ?? '-') ?>)</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Jumlah Media</div>
                        <div class="fw-500"><?= count($data['media']) ?> file (<?= count($fotoMedia) ?> foto, <?= count($videoMedia) ?> video)</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Catatan</div>
                        <div class="fw-500"><?= nl2br(htmlspecialchars($data['catatan'] ?: '—')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Pohon Terkait -->
        <div class="card mb-4">
           <div class="card-header d-flex align-items-center gap-2">
    <i class="bi bi-tree text-success"></i>
    <strong>Data Pohon Terkait</strong>
    <span class="ms-auto d-flex gap-2">
        <a href="../maps.php?tree_id=<?= (int) $data['pohon_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-geo-alt me-1"></i>Lihat di Peta
        </a>
        <a href="detail_pohon.php?id=<?= (int) $data['pohon_id'] ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Detail Pohon
        </a>
    </span>
</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Nama Lokal</div>
                        <div class="fw-500"><?= htmlspecialchars($data['nama_lokal'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Nama Latin</div>
                        <div class="fw-500 fst-italic"><?= htmlspecialchars($data['nama_latin'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Family</div>
                        <div class="fw-500"><?= htmlspecialchars($data['family'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Lokasi</div>
                        <div class="fw-500"><?= htmlspecialchars($data['nama_jalan'] ?? '—') ?>, <?= htmlspecialchars($data['kelurahan'] ?? '') ?>, <?= htmlspecialchars($data['kecamatan'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Kondisi Kesehatan Pohon (Terkini)</div>
                        <div class="fw-500"><?= htmlspecialchars($data['kesehatan_pohon_saat_ini'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Galeri Media -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-images text-info"></i>
                <strong>Dokumentasi Foto</strong>
                <span class="badge bg-secondary ms-1"><?= count($fotoMedia) ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($fotoMedia)): ?>
                <div class="row g-3">
                    <?php foreach ($fotoMedia as $m): ?>
                    <div class="col-6 col-md-3">
                        <a href="../<?= htmlspecialchars($m['file_path']) ?>" target="_blank">
                            <img src="../<?= htmlspecialchars($m['file_path']) ?>" class="w-100 rounded-2 border" style="height:130px; object-fit:cover;" alt="<?= htmlspecialchars($m['file_name']) ?>">
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0" style="font-size:0.85rem;">Belum ada foto dokumentasi.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-camera-reels text-warning"></i>
                <strong>Dokumentasi Video</strong>
                <span class="badge bg-secondary ms-1"><?= count($videoMedia) ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($videoMedia)): ?>
                <div class="row g-3">
                    <?php foreach ($videoMedia as $m): ?>
                    <div class="col-12 col-md-6">
                        <video src="../<?= htmlspecialchars($m['file_path']) ?>" class="w-100 rounded-2 border" style="max-height:260px;" controls></video>
                        <div class="text-muted mt-1" style="font-size:0.72rem;"><?= htmlspecialchars($m['file_name']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0" style="font-size:0.85rem;">Belum ada video dokumentasi.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <a href="monitoring.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
            <a href="edit_monitoring.php?id=<?= (int) $data['id'] ?>" class="btn btn-warning text-white"><i class="bi bi-pencil me-1"></i>Edit</a>
        </div>

    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
