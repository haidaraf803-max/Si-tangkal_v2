<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/PengajuanModel.php';
require_once 'core/Rbac.php';

Rbac::requireAccess($config, 'pengajuan', 'view');
$canEditPengajuan = Rbac::can($config, 'pengajuan', 'edit');
$myRoleCode       = Rbac::currentRoleCode($config);
$isSuperadmin     = Rbac::isSuperadmin($config);
$currentUserId    = (int) ($_SESSION['admin']['UserId'] ?? 0);

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

$alertMsg  = '';
$alertType = '';

// Peran yang berhak melakukan tiap aksi (superadmin selalu boleh)
// Langkah "Divalidasi" oleh Validator sudah dihapus dari alur.
$bolehSurvey    = $canEditPengajuan && ($isSuperadmin || $myRoleCode === 'petugas_survey');
$bolehEksekusi  = $canEditPengajuan && ($isSuperadmin || $myRoleCode === 'tim_tangkas');

// ===== AKSI: SUBMIT HASIL SURVEY (Petugas Survey) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'submit_survey') {
    if (!$bolehSurvey) {
        $alertMsg  = 'Peran Anda tidak berhak mengisi hasil survey.';
        $alertType = 'warning';
    } else {
        $hasil   = ($_POST['hasil_survey'] ?? '') === 'perlu_pemangkasan' ? 'perlu_pemangkasan' : 'tidak_perlu';
        $catatan = trim($_POST['catatan_survey'] ?? '');
        $ok = $model->submitSurvey($id, $currentUserId, $hasil, $catatan);
        $alertMsg  = $ok ? 'Hasil survey berhasil disimpan.' : 'Gagal menyimpan hasil survey.';
        $alertType = $ok ? 'success' : 'danger';
        if ($ok) {
            $data = $model->getById($id);
        }
    }
}

// ===== AKSI: EKSEKUSI / DOKUMENTASI LAPANGAN (Tim Tangkas) =====
// Langkah "Divalidasi" oleh Validator sudah dihapus. Setelah hasil survey
// menyatakan "perlu_pemangkasan", Tim Tangkas langsung dapat mengunggah
// foto sesudah penanganan di sini.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'eksekusi') {
    if (!$bolehEksekusi) {
        $alertMsg  = 'Peran Anda tidak berhak mengunggah dokumentasi eksekusi.';
        $alertType = 'warning';
    } else {
        $uploadDir = __DIR__ . '/../images/';
        $fotoSesudahName = '';

        if (!empty($_FILES['foto_sesudah']['name'])) {
            $fotoSesudahName = 'sesudah_' . $id . '_' . time() . '_' . basename($_FILES['foto_sesudah']['name']);
            move_uploaded_file($_FILES['foto_sesudah']['tmp_name'], $uploadDir . $fotoSesudahName);
        }

        $ok = $model->eksekusi($id, $currentUserId, $fotoSesudahName);
        $alertMsg  = $ok ? 'Foto sesudah penanganan berhasil disimpan, pengajuan selesai.' : 'Gagal menyimpan dokumentasi eksekusi.';
        $alertType = $ok ? 'success' : 'danger';
        if ($ok) {
            $data = $model->getById($id);
        }
    }
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

        <!-- Alert -->
        <?php if ($alertMsg): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $alertType === 'success' ? 'check-circle' : ($alertType === 'danger' ? 'x-circle' : 'exclamation-triangle') ?> me-2"></i>
            <?= htmlspecialchars($alertMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- ======= TIMELINE STATUS TAHAP ======= -->
        <?php
        // Catatan: langkah "Divalidasi" oleh Validator sudah dihapus dari alur.
        $tahapan = [
            'diajukan'   => ['label' => 'Diajukan',    'icon' => 'bi-file-earmark-plus'],
            'disurvey'   => ['label' => 'Disurvey',    'icon' => 'bi-binoculars'],
            'selesai'    => ['label' => 'Selesai',     'icon' => 'bi-check-circle'],
        ];
        $urutan = array_keys($tahapan);
        $tahapSekarang = $data['status_tahap'] ?? 'diajukan';
        // Data lama yang masih berstatus 'divalidasi'/'ditangani' otomatis
        // dianggap sudah "disurvey" (siap dieksekusi Tim Tangkas).
        if (in_array($tahapSekarang, ['divalidasi', 'ditangani'], true)) {
            $tahapSekarang = 'disurvey';
        }
        $indexSekarang = array_search($tahapSekarang, $urutan, true) ?: 0;
        ?>
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <?php foreach ($urutan as $i => $key): ?>
                    <div class="text-center flex-fill">
                        <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle mb-1"
                             style="width:38px; height:38px; background:<?= $i <= $indexSekarang ? 'var(--accent, #059669)' : '#e2e8f0' ?>; color:<?= $i <= $indexSekarang ? '#fff' : '#94a3b8' ?>;">
                            <i class="bi <?= $tahapan[$key]['icon'] ?>"></i>
                        </div>
                        <div style="font-size:0.72rem; font-weight:600; color:<?= $i <= $indexSekarang ? 'var(--text-primary)' : '#94a3b8' ?>;">
                            <?= $tahapan[$key]['label'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

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
                            <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Nomor Telepon</div>
                            <div class="fw-600 mt-1"><?= htmlspecialchars($data['Nomor_Telepon'] ?? '-') ?></div>
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

            </div><!-- /.card-body -->
        </div><!-- /.card -->

        <!-- ======= PANEL AKSI ALUR BERJENJANG ======= -->

        <?php if ($bolehSurvey && in_array($tahapSekarang, ['diajukan'], true)): ?>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-binoculars text-primary me-2"></i><strong>Input Hasil Survey Lapangan</strong></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="form_action" value="submit_survey">
                    <div class="mb-3">
                        <label class="form-label">Hasil Survey <span class="text-danger">*</span></label>
                        <select class="form-select" name="hasil_survey" required>
                            <option value="perlu_pemangkasan">Perlu Pemangkasan / Penanganan</option>
                            <option value="tidak_perlu">Tidak Perlu Ditangani</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Survey</label>
                        <textarea class="form-control" name="catatan_survey" rows="3" placeholder="Kondisi pohon, alasan, dsb."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Kirim Hasil Survey</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($bolehEksekusi && $tahapSekarang === 'disurvey' && ($data['hasil_survey'] ?? '') === 'perlu_pemangkasan'): ?>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-tools text-warning me-2"></i><strong>Dokumentasi Eksekusi Lapangan</strong></div>
            <div class="card-body">
                <p class="text-muted" style="font-size:0.85rem;">Foto "sebelum" sudah tersedia dari foto wajib saat pengajuan dibuat. Tim Tangkas hanya perlu mengunggah foto sesudah penanganan.</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="form_action" value="eksekusi">
                    <div class="mb-3">
                        <label class="form-label">Foto Sesudah <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="foto_sesudah" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-warning text-white mt-3"><i class="bi bi-upload me-1"></i> Simpan & Selesaikan</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-3">
            <a href="pengajuan.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <?php if ($canEditPengajuan): ?>
            <a href="edit_pengajuan.php?id=<?= $id ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit Data
            </a>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>