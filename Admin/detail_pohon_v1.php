<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/PohonModel.php';

$model = new PohonModel($config);

if (!isset($_GET['id'])) {
    header("Location: pohon.php");
    exit;
}

$id   = (int) $_GET['id'];
$data = $model->getById($id);

if (!$data) {
    header("Location: pohon.php");
    exit;
}

$isDone   = (strtolower($data['keterangan'] ?? '') === 'sudah');
$pageTitle  = 'Detail Kondisi Pohon';
$activePage = 'pohon';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:0.8rem;">
        <li class="breadcrumb-item"><a href="index.php" class="text-success">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="pohon.php" class="text-success">Pohon</a></li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">

        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-text text-primary"></i>
                <strong>Detail Data Pohon</strong>
                <span class="ms-auto">
                    <?php if ($data['kesehatan'] == 'Sehat'): ?>
                        <span class="badge rounded-pill bg-success-subtle text-success">
                            <i class="bi bi-check-circle me-1"></i>Sehat
                        </span>
                    <?php elseif ($data['kesehatan'] == 'Kurang Sehat'): ?>
                        <span class="badge rounded-pill bg-success-subtle text-warning">
                            <i class="bi bi-check-circle me-1"></i>Kurang Sehat
                        </span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-danger-subtle text-danger">
                            <i class="bi bi-clock me-1"></i>Sakit
                        </span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body p-4">

                <div class="row g-4">

                    <!-- Kolom Kiri: Info Utama -->
                    <div class="col-md-6">
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">No Pohon</div>
                            <div class="fw-600 mt-1"><?= htmlspecialchars($data['no_pohon']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Nama Lokal</div>
                            <div class="fw-600 mt-1"><?= htmlspecialchars($data['nama_lokal']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Nama Latin</div>
                            <div class="mt-1"><?= htmlspecialchars($data['nama_latin']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Family</div>
                            <div class="mt-1"><?= htmlspecialchars($data['family']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Tahun Tanam</div>
                            <div class="mt-1"><?= htmlspecialchars($data['tahun_tanam']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Habitus</div>
                            <div class="mt-1"><?= htmlspecialchars($data['habitus']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Status</div>
                            <div class="mt-1"><?= htmlspecialchars($data['status_kel']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Volume</div>
                            <div class="mt-1"><?= htmlspecialchars($data['volume']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Kelas Awet</div>
                            <div class="mt-1"><?= htmlspecialchars($data['kelas_awet']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Kelas Kuat</div>
                            <div class="mt-1"><?= htmlspecialchars($data['kelas_kuat']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Berat Jenis</div>
                            <div class="mt-1"><?= htmlspecialchars($data['berat_jenis']) ?></div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Survey & Dokumentasi -->
                    <div class="col-md-6">
                    <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Status Kesehatan</div>
                            <div class="mt-1">
                                <?php if ($data['kesehatan'] == 'Sehat'): ?>
                                    <span class="badge rounded-pill bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i>Sehat
                                    </span>
                                <?php elseif ($data['kesehatan'] == 'Kurang Sehat'): ?>
                                    <span class="badge rounded-pill bg-success-subtle text-warning">
                                        <i class="bi bi-check-circle me-1"></i>Kurang Sehat
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-danger-subtle text-danger">
                                        <i class="bi bi-clock me-1"></i>Sakit
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Serapan CO2</div>
                            <div class="mt-1"><?= htmlspecialchars($data['serapan_co']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Produksi Oksigen</div>
                            <div class="mt-1"><?= htmlspecialchars($data['produksi_o']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Nama Jalan</div>
                            <div class="mt-1"><?= htmlspecialchars($data['nama_jalan']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Kelurahan</div>
                            <div class="mt-1"><?= htmlspecialchars($data['kelurahan']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Kecamatan</div>
                            <div class="mt-1"><?= htmlspecialchars($data['kecamatan']) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Keterangan</div>
                            <div class="mt-1 p-3 rounded-2" style="background:#f8fafc; border:1px solid var(--border-color); min-height:80px; font-size:0.85rem; line-height:1.6;">
                                <?php if (!empty($data['keterangan'])): ?>
                                    <?= nl2br(htmlspecialchars($data['keterangan'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Belum ada catatan</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Foto</div>
                            <div class="row mt-2 g-2">
                                <div class="col-sm-12">
                                    <!--<div class="mb-1" style="font-size:0.8rem; font-weight:600;">Sebelum</div>-->
                                    <?php if (!empty($data['foto'])): ?>
                                        <img src="../assets/foto/<?= htmlspecialchars($data['foto']) ?>"
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
                                <!--<div class="col-sm-6">
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
                                </div>-->
                            </div>
                        </div>
                    </div>

                </div><!-- /.row -->

                <hr class="my-4">

                <div class="d-flex gap-2">
                    <a href="pohon.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="edit_pohon.php?id=<?= $id ?>" class="btn btn-warning">
                        <i class="bi bi-pencil me-1"></i> Edit Data
                    </a>
                </div>

            </div><!-- /.card-body -->
        </div><!-- /.card -->

    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
