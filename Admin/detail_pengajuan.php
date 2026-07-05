<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/PengajuanModel.php';

$model = new PengajuanModel($config);

if (!isset($_GET['id'])) {
    header("Location: pengajuan.php");
    exit;
}

$id   = (int) $_GET['id'];
$data = $model->getById($id);

if (!$data) {
    header("Location: pengajuan.php");
    exit;
}

$isDone   = (strtolower($data['Keterangan'] ?? '') === 'sudah');
$pageTitle  = 'Detail Pengajuan';
$activePage = 'pengajuan';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:0.8rem;">
        <li class="breadcrumb-item"><a href="index.php" class="text-success">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="pengajuan.php" class="text-success">Pengajuan</a></li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">

        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-text text-primary"></i>
                <strong>Detail Data Pengajuan</strong>
                <span class="ms-auto">
                    <?php if ($isDone): ?>
                        <span class="badge rounded-pill bg-success-subtle text-success">
                            <i class="bi bi-check-circle me-1"></i>Sudah Diproses
                        </span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-danger-subtle text-danger">
                            <i class="bi bi-clock me-1"></i>Belum Diproses
                        </span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body p-4">

                <div class="row g-4">

                    <!-- Kolom Kiri: Info Utama -->
                    <div class="col-md-6">
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">No Surat</div>
                            <div class="fw-600 mt-1"><?= htmlspecialchars($data['No_Surat']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Nama Pemohon</div>
                            <div class="fw-600 mt-1"><?= htmlspecialchars($data['Nama_Pemohon']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Lokasi Pohon</div>
                            <div class="mt-1"><?= htmlspecialchars($data['Lokasi_Pohon']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Disposisi Surat</div>
                            <div class="mt-1">
                                <?php if (!empty($data['Disposisi_Surat'])): ?>
                                    <i class="bi bi-calendar3 me-1 text-muted"></i>
                                    <?= htmlspecialchars($data['Disposisi_Surat']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Tanggal Penanganan</div>
                            <div class="mt-1">
                                <?php if (!empty($data['Tanggal_Penanganan'])): ?>
                                    <i class="bi bi-calendar-check me-1 text-muted"></i>
                                    <?= htmlspecialchars($data['Tanggal_Penanganan']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Survey & Dokumentasi -->
                    <div class="col-md-6">
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Status Keterangan</div>
                            <div class="mt-1">
                                <?php if ($isDone): ?>
                                    <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> Sudah
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2">
                                        <i class="bi bi-x-circle-fill me-1"></i> Belum
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Survey Pohon</div>
                            <div class="mt-1 p-3 rounded-2" style="background:#f8fafc; border:1px solid var(--border-color); min-height:80px; font-size:0.85rem; line-height:1.6;">
                                <?php if (!empty($data['Survey_Pohon'])): ?>
                                    <?= nl2br(htmlspecialchars($data['Survey_Pohon'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Belum ada catatan survey</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Dokumentasi Pemangkasan</div>
                            <div class="row mt-2 g-2">
                                <div class="col-sm-6">
                                    <div class="mb-1" style="font-size:0.8rem; font-weight:600;">Sebelum</div>
                                    <?php if (!empty($data['Dokumentasi'])): ?>
                                        <img src="../images/<?= htmlspecialchars($data['Dokumentasi']) ?>"
                                             alt="Sebelum"
                                             class="rounded-2 shadow-sm"
                                             style="max-width:100%; max-height:200px; object-fit:cover; border:1px solid var(--border-color);">
                                    <?php else: ?>
                                        <div class="p-3 rounded-2 text-center text-muted" style="background:#f8fafc; border:1px dashed var(--border-color);">
                                            <i class="bi bi-image fs-3 d-block mb-1"></i>
                                            Belum ada foto
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-1" style="font-size:0.8rem; font-weight:600;">Sesudah</div>
                                    <?php if (!empty($data['DokumentasiAfter'])): ?>
                                        <img src="../images/<?= htmlspecialchars($data['DokumentasiAfter']) ?>"
                                             alt="Sesudah"
                                             class="rounded-2 shadow-sm"
                                             style="max-width:100%; max-height:200px; object-fit:cover; border:1px solid var(--border-color);">
                                    <?php else: ?>
                                        <div class="p-3 rounded-2 text-center text-muted" style="background:#f8fafc; border:1px dashed var(--border-color);">
                                            <i class="bi bi-image fs-3 d-block mb-1"></i>
                                            Belum ada foto
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /.row -->

                <hr class="my-4">

                <div class="d-flex gap-2">
                    <a href="pengajuan.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="edit_pengajuan.php?id=<?= $id ?>" class="btn btn-warning">
                        <i class="bi bi-pencil me-1"></i> Edit Data
                    </a>
                </div>

            </div><!-- /.card-body -->
        </div><!-- /.card -->

    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
