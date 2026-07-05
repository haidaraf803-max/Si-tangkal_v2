<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/PengajuanModel.php';

$model       = new PengajuanModel($config);
$alertMsg    = '';
$alertType   = '';

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    $hapusId = (int) $_GET['hapus'];
    if ($model->delete($hapusId)) {
        header("Location: pengajuan.php?deleted=1");
    } else {
        header("Location: pengajuan.php?deleted=0");
    }
    exit;
}

// Alert dari redirect
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] == '1' ? 'Data pengajuan berhasil dihapus.' : 'Gagal menghapus data.';
    $alertType = $_GET['deleted'] == '1' ? 'success' : 'danger';
}

// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $noSurat = trim($_POST['no_surat'] ?? '');
    $nama    = trim($_POST['nama'] ?? '');
    $lokasi  = trim($_POST['lokasi'] ?? '');

    if ($noSurat && $nama && $lokasi) {
        if ($model->create($noSurat, $nama, $lokasi)) {
            $alertMsg  = 'Data pengajuan berhasil ditambahkan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Gagal menambahkan data.';
            $alertType = 'danger';
        }
    } else {
        $alertMsg  = 'Semua field wajib diisi.';
        $alertType = 'warning';
    }
}

// ===== SEARCH / GET ALL =====
$keyword = trim($_GET['cari'] ?? '');
$data    = $keyword ? $model->search($keyword) : $model->getAll();

$pageTitle  = 'Data Pengajuan';
$activePage = 'pengajuan';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Data Pengajuan</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Kelola semua data pengajuan penanganan pohon</p>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pengajuan
    </button>
</div>

<!-- Alert -->
<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $alertType === 'success' ? 'check-circle' : ($alertType === 'danger' ? 'x-circle' : 'exclamation-triangle') ?> me-2"></i>
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ======= SEARCH & TABLE ======= -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-table me-2"></i>Daftar Pengajuan
            <span class="badge bg-secondary ms-1"><?= count($data) ?></span>
        </span>
        <form method="GET" class="d-flex gap-2" style="min-width:240px;">
            <input type="text" name="cari" class="form-control form-control-sm"
                   placeholder="Cari No Surat / Nama..."
                   value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-search"></i></button>
            <?php if ($keyword): ?>
            <a href="pengajuan.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No</th>
                        <th>No Surat</th>
                        <th>Nama Pemohon</th>
                        <th>Lokasi Pohon</th>
                        <th>Disposisi</th>
                        <th>Tgl Penanganan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php $no = 1; foreach ($data as $row):
                            $isDone = (strtolower($row['Keterangan'] ?? '') === 'sudah');
                        ?>
                        <tr onclick="window.location='detail_pengajuan.php?id=<?= (int)$row['Id'] ?>'">
                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['No_Surat']) ?></td>
                            <td><?= htmlspecialchars($row['Nama_Pemohon']) ?></td>
                            <td>
                                <span style="max-width:180px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                      title="<?= htmlspecialchars($row['Lokasi_Pohon']) ?>">
                                    <?= htmlspecialchars($row['Lokasi_Pohon']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['Disposisi_Surat'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['Tanggal_Penanganan'] ?? '-') ?></td>
                            <td class="text-center">
                                <?php if ($isDone): ?>
                                    <span class="badge rounded-pill bg-success-subtle text-success">Sudah</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-danger-subtle text-danger">Belum</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-3">
                                <a href="edit_pengajuan.php?id=<?= (int)$row['Id'] ?>"
                                   class="btn btn-sm btn-outline-warning"
                                   title="Edit"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="pengajuan.php?hapus=<?= (int)$row['Id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Hapus"
                                   onclick="event.stopPropagation(); return confirm('Yakin ingin menghapus pengajuan ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                <?= $keyword ? "Tidak ditemukan data untuk \"<strong>" . htmlspecialchars($keyword) . "</strong>\"" : 'Belum ada data pengajuan' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======= MODAL: TAMBAH PENGAJUAN ======= -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius-lg); border:none; box-shadow:var(--shadow-lg);">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header" style="border-bottom:1px solid var(--border-color);">
                    <h5 class="modal-title fw-bold" id="modalTambahLabel">
                        <i class="bi bi-plus-circle me-2 text-success"></i>Tambah Pengajuan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label" for="no_surat">No Surat <span class="text-danger">*</span></label>
                        <input type="text" id="no_surat" name="no_surat" class="form-control"
                               placeholder="Contoh: 001/DLH/2024" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nama">Nama Pemohon <span class="text-danger">*</span></label>
                        <input type="text" id="nama" name="nama" class="form-control"
                               placeholder="Nama lengkap pemohon" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="lokasi">Lokasi Pohon <span class="text-danger">*</span></label>
                        <input type="text" id="lokasi" name="lokasi" class="form-control"
                               placeholder="Alamat lokasi pohon" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
