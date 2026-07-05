<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/UserModel.php';

$model     = new UserModel($config);
$alertMsg  = '';
$alertType = '';

$roleOptions =  ['Admin', 'Petugas Lapangan'];

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    $hapusId = (int) $_GET['hapus'];
    $currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

    if ($hapusId === $currentUserId) {
        header("Location: users.php?deleted=self");
        exit;
    }

    $ok = $model->delete($hapusId);
    header("Location: users.php?deleted=" . ($ok ? '1' : '0'));
    exit;
}

if (isset($_GET['deleted'])) {
    if ($_GET['deleted'] === 'self') {
        $alertMsg  = 'Tidak bisa menghapus akun yang sedang Anda gunakan untuk login.';
        $alertType = 'warning';
    } else {
        $alertMsg  = $_GET['deleted'] == '1' ? 'Data pengguna berhasil dihapus.' : 'Gagal menghapus data pengguna.';
        $alertType = $_GET['deleted'] == '1' ? 'success' : 'danger';
    }
}

// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $type     = trim($_POST['type'] ?? '');
    $name     = trim($_POST['name'] ?? '');

    if ($username !== '' && $password !== '' && $email !== '' && $type !== '' && $name !== '') {
        if ($model->usernameExists($username)) {
            $alertMsg  = "Username \"$username\" sudah digunakan.";
            $alertType = 'warning';
        } else {
            $ok = $model->create($username, $password, $email, $type, $name);
            $alertMsg  = $ok ? 'Pengguna baru berhasil ditambahkan.' : 'Gagal menambahkan pengguna.';
            $alertType = $ok ? 'success' : 'danger';
        }
    } else {
        $alertMsg  = 'Semua field wajib diisi.';
        $alertType = 'warning';
    }
}

// ===== EDIT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $userId   = (int) ($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $type     = trim($_POST['type'] ?? '');
    $name     = trim($_POST['name'] ?? '');

    if ($userId > 0 && $username !== '' && $email !== '' && $type !== '' && $name !== '') {
        if ($model->usernameExists($username, $userId)) {
            $alertMsg  = "Username \"$username\" sudah digunakan oleh pengguna lain.";
            $alertType = 'warning';
        } else {
            $ok = $model->update($userId, $username, $password, $email, $type, $name);
            $alertMsg  = $ok ? 'Data pengguna berhasil diperbarui.' : 'Gagal memperbarui data pengguna.';
            $alertType = $ok ? 'success' : 'danger';
        }
    } else {
        $alertMsg  = 'Username, email, tipe, dan nama wajib diisi.';
        $alertType = 'warning';
    }
}

// ===== SEARCH / GET ALL =====
$keyword = trim($_GET['cari'] ?? '');
$data    = $model->getAll($keyword);
$totalUsers = $model->countAll();

$pageTitle  = 'Manajemen Pengguna';
$activePage = 'users';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Manajemen Pengguna</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Kelola akun pengguna aplikasi Si-TANGKAL (t_users)</p>
    </div>
    <div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
            <i class="bi bi-person-plus me-1"></i> Tambah Pengguna
        </button>
    </div>
</div>

<!-- Mini Stat -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-primary fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalUsers ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Total Pengguna</div>
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
        <span><i class="bi bi-table me-2"></i>Data Pengguna
            <span class="badge bg-secondary ms-1"><?= count($data) ?></span>
        </span>
        <form method="GET" class="d-flex gap-2" style="min-width:240px;">
            <input type="text" name="cari" class="form-control form-control-sm"
                   placeholder="Cari username / nama / email..."
                   value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-search"></i></button>
            <?php if ($keyword): ?>
            <a href="users.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th class="text-center">Tipe</th>
                        <th>Dibuat</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['Username']) ?></td>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['Email']) ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-success-subtle text-success"><?= htmlspecialchars($row['Type']) ?></span>
                            </td>
                            <td class="text-muted"><?= $row['CreatedDate'] ? htmlspecialchars(date('d M Y', strtotime($row['CreatedDate']))) : '—' ?></td>
                            <td class="text-center pe-3">
                                <button type="button" class="btn btn-sm btn-outline-info" title="Detail"
                                        data-bs-toggle="modal" data-bs-target="#modalDetailUser<?= (int) $row['UserId'] ?>">
                                    <i class="bi bi-info"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" title="Edit"
                                        data-bs-toggle="modal" data-bs-target="#modalEditUser<?= (int) $row['UserId'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="users.php?hapus=<?= (int) $row['UserId'] ?>"
                                   class="btn btn-sm btn-outline-danger" title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus pengguna <?= htmlspecialchars(addslashes($row['Username'])) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>

                                <!-- Modal Detail -->
                                <div class="modal fade" id="modalDetailUser<?= (int) $row['UserId'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-person-vcard text-info me-2"></i>Detail Pengguna</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-6">
                                                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Username</div>
                                                        <div class="fw-500"><?= htmlspecialchars($row['Username']) ?></div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Tipe</div>
                                                        <div class="fw-500"><?= htmlspecialchars($row['Type']) ?></div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Nama Lengkap</div>
                                                        <div class="fw-500"><?= htmlspecialchars($row['Name']) ?></div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Email</div>
                                                        <div class="fw-500"><?= htmlspecialchars($row['Email']) ?></div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Tanggal Dibuat</div>
                                                        <div class="fw-500"><?= $row['CreatedDate'] ? htmlspecialchars(date('d F Y H:i', strtotime($row['CreatedDate']))) : '—' ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Edit -->
                                <div class="modal fade" id="modalEditUser<?= (int) $row['UserId'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form method="POST" class="modal-content">
                                            <input type="hidden" name="user_id" value="<?= (int) $row['UserId'] ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Pengguna</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($row['Username']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($row['Name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['Email']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Tipe / Role <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="type" required>
                                                        <?php foreach ($roleOptions as $opt): ?>
                                                        <option value="<?= $opt ?>" <?= ($row['Type'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                                                        <?php endforeach; ?>
                                                        <?php if (!in_array($row['Type'], $roleOptions, true)): ?>
                                                        <option value="<?= htmlspecialchars($row['Type']) ?>" selected><?= htmlspecialchars($row['Type']) ?></option>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-1">
                                                    <label class="form-label">Password Baru</label>
                                                    <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                                                    <div class="form-text">Biarkan kosong untuk mempertahankan password lama.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_user" class="btn btn-warning text-white">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-people fs-2 d-block mb-2"></i>
                                <?= $keyword ? "Tidak ditemukan data untuk \"<strong>" . htmlspecialchars($keyword) . "</strong>\"" : 'Belum ada data pengguna' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======= MODAL: TAMBAH USER ======= -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus text-success me-2"></i>Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipe / Role <span class="text-danger">*</span></label>
                    <select class="form-select" name="type" required>
                        <option value="">-- Pilih Tipe --</option>
                        <?php foreach ($roleOptions as $opt): ?>
                        <option value="<?= $opt ?>"><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-1">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add_user" class="btn btn-success">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
