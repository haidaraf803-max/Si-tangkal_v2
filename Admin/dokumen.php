<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK =====
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'dokumen', 'view');
$canCreate = Rbac::can($config, 'dokumen', 'create');
$canDelete = Rbac::can($config, 'dokumen', 'delete');
require_once 'core/DokumenModel.php';

$model     = new DokumenModel($config);
$alertMsg  = '';
$alertType = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== UPLOAD DOKUMEN BARU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!$canCreate) {
        $alertMsg  = 'Peran Anda tidak memiliki izin mengunggah dokumen.';
        $alertType = 'warning';
    } else {
        $namaDokumen = trim($_POST['nama_dokumen'] ?? '');
        $keterangan  = trim($_POST['keterangan'] ?? '');

        if ($namaDokumen === '') {
            $alertMsg  = 'Nama dokumen wajib diisi.';
            $alertType = 'warning';
        } else {
            $uploaded = $model->uploadFile($_FILES['file_dokumen'] ?? null);
            if ($uploaded === null) {
                $alertMsg  = 'File wajib diunggah. Format yang didukung: PDF, Word, Excel, PowerPoint, ZIP, atau gambar.';
                $alertType = 'warning';
            } else {
                $ok = $model->create($namaDokumen, $keterangan, $uploaded, $currentUserId);
                $alertMsg  = $ok ? 'Dokumen berhasil diunggah.' : 'Gagal menyimpan data dokumen.';
                $alertType = $ok ? 'success' : 'danger';
            }
        }
    }
}

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    if (!$canDelete) { header('Location: dokumen.php?deleted=forbidden'); exit; }
    $model->delete((int) $_GET['hapus']);
    header('Location: dokumen.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] === 'forbidden' ? 'Peran Anda tidak memiliki izin menghapus dokumen.' : 'Dokumen berhasil dihapus.';
    $alertType = $_GET['deleted'] === 'forbidden' ? 'warning' : 'success';
}

$data = $model->getAll();

$pageTitle  = 'Dokumen';
$activePage = 'dokumen';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';

/** Format ukuran file jadi KB/MB yang enak dibaca. */
function formatUkuran(?int $bytes): string
{
    if (!$bytes) return '-';
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
?>

<style>
    /* ===== Modal cantik terpakai bersama (pola sama dgn modul lain) ===== */
    .modal-nice .modal-content {
        display: flex !important;
        flex-direction: column !important;
        border: 0; border-radius: 18px; overflow: hidden;
        box-shadow: 0 20px 60px rgba(15,23,42,.18);
    }
    .modal-nice .modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); background: linear-gradient(135deg,#ecfdf5,#f0fdf4); }
    .modal-nice .modal-title { display: flex; align-items: center; gap: .75rem; margin: 0; }
    .modal-nice .icon-badge {
        width: 40px; height: 40px; border-radius: 12px; flex: 0 0 auto;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff;
        background: linear-gradient(135deg,#10b981,#059669);
    }
    .modal-nice .title-text { font-size: 1rem; font-weight: 700; color: var(--text-primary); line-height: 1.3; }
    .modal-nice .modal-subtitle { font-size: .76rem; font-weight: 400; color: var(--text-muted); margin-top: .1rem; }
    .modal-nice .modal-body { padding: 1.5rem; background: #fff; }
    .modal-nice .form-label { font-weight: 600; font-size: .74rem; letter-spacing: .3px; text-transform: uppercase; color: #475569; margin-bottom: .4rem; }
    .modal-nice .form-control, .modal-nice .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: .55rem .8rem; font-size: .875rem; }
    .modal-nice .form-control:focus, .modal-nice .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(5,150,105,.12); }
    .modal-nice .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; }
    .modal-nice .modal-footer .btn { border-radius: 10px; font-weight: 600; padding: .55rem 1.2rem; font-size: .82rem; }
    .modal-nice .btn-save { background: linear-gradient(135deg,#10b981,#059669); border: 0; color: #fff; }
    .modal-nice .btn-save:hover { filter: brightness(0.95); color: #fff; }
    .modal-nice .form-text { font-size: .72rem; }

    .doc-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(16,185,129,.1); color: #059669; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex: 0 0 auto; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Dokumen</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Daftar dokumen yang bisa diunduh</p>
    </div>
    <?php if ($canCreate): ?>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-upload me-1"></i> Unggah Dokumen
    </button>
    <?php endif; ?>
</div>

<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-file-earmark-arrow-down me-2"></i>Daftar Dokumen <span class="badge bg-secondary ms-1"><?= count($data) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Nama Dokumen</th>
                        <th>Keterangan</th>
                        <th>Ukuran</th>
                        <th>Diunggah</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="doc-icon"><i class="bi bi-file-earmark-text"></i></div>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></div>
                                        <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($row['file_asli'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                            <td><?= formatUkuran($row['ukuran_bytes'] ?? null) ?></td>
                            <td>
                                <div><?= htmlspecialchars(date('d M Y', strtotime($row['dibuat_pada']))) ?></div>
                                <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($row['diunggah_oleh_nama'] ?? '-') ?></div>
                            </td>
                            <td class="text-center pe-3">
                                <a href="../<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-sm btn-outline-success me-1" download title="Unduh">
                                    <i class="bi bi-download"></i> Unduh
                                </a>
                                <?php if ($canDelete): ?>
                                <a href="?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus dokumen ini?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-1"></i> Belum ada dokumen yang diunggah</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============ MODAL: Unggah Dokumen ============ -->
<?php if ($canCreate): ?>
<div class="modal fade modal-nice" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <div class="modal-title">
                        <div class="icon-badge"><i class="bi bi-upload"></i></div>
                        <div>
                            <div class="title-text">Unggah Dokumen</div>
                            <span class="modal-subtitle d-block">Tambahkan dokumen baru ke daftar unduhan</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Dokumen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_dokumen" placeholder="mis. SOP Pemangkasan Pohon" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" class="form-control" name="keterangan" placeholder="Opsional">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file_dokumen" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.jpg,.jpeg,.png" required>
                        <div class="form-text">Format: PDF, Word, Excel, PowerPoint, ZIP, atau gambar.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add" class="btn btn-save">Unggah</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'layouts/footer.php'; ?>
