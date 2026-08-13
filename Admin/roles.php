<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/RoleModel.php';
require_once 'core/Rbac.php';

// Manajemen Role: default hanya Superadmin (lihat db/migration_rbac.sql)
Rbac::requireAccess($config, 'roles', 'view');
$canCreateRole = Rbac::can($config, 'roles', 'create');
$canEditRole   = Rbac::can($config, 'roles', 'edit');
$canDeleteRole = Rbac::can($config, 'roles', 'delete');

$model     = new RoleModel($config);
$alertMsg  = '';
$alertType = '';

// ===== CREATE ROLE BARU (custom, di luar 8 peran bawaan dokumen) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    if (!$canCreateRole) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menambah role.';
        $alertType = 'warning';
    } else {
        $code = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $_POST['code'] ?? '')));
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($code !== '' && $name !== '') {
            if ($model->codeExists($code)) {
                $alertMsg  = "Kode role \"$code\" sudah dipakai.";
                $alertType = 'warning';
            } else {
                $model->create($code, $name, $desc);
                $alertMsg  = 'Role baru berhasil ditambahkan. Silakan atur izin menunya.';
                $alertType = 'success';
            }
        } else {
            $alertMsg  = 'Kode dan nama role wajib diisi.';
            $alertType = 'warning';
        }
    }
}

// ===== UPDATE INFO ROLE (nama & deskripsi) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_role'])) {
    if (!$canEditRole) {
        $alertMsg  = 'Peran Anda tidak memiliki izin mengubah role.';
        $alertType = 'warning';
    } else {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        if ($roleId > 0 && $name !== '') {
            $model->update($roleId, $name, $desc);
            $alertMsg  = 'Data role berhasil diperbarui.';
            $alertType = 'success';
        }
    }
}

// ===== SIMPAN MATRIKS IZIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    if (!$canEditRole) {
        $alertMsg  = 'Peran Anda tidak memiliki izin mengubah izin akses.';
        $alertType = 'warning';
    } else {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $raw    = $_POST['perm'] ?? []; // perm[menu_id][view|create|edit|delete] = 1

        $permissions = [];
        foreach ($raw as $menuId => $flags) {
            $permissions[(int) $menuId] = [
                'view'   => !empty($flags['view']),
                'create' => !empty($flags['create']),
                'edit'   => !empty($flags['edit']),
                'delete' => !empty($flags['delete']),
            ];
        }

        if ($roleId > 0) {
            $ok = $model->savePermissions($roleId, $permissions);
            $alertMsg  = $ok ? 'Matriks izin akses berhasil disimpan.' : 'Gagal menyimpan izin akses.';
            $alertType = $ok ? 'success' : 'danger';
        }
    }
}

// ===== DELETE ROLE =====
if (isset($_GET['hapus'])) {
    if (!$canDeleteRole) {
        header('Location: roles.php?deleted=forbidden');
        exit;
    }
    $result = $model->delete((int) $_GET['hapus']);
    if ($result === true) {
        header('Location: roles.php?deleted=1');
    } else {
        header('Location: roles.php?deleted=0&msg=' . urlencode(is_string($result) ? $result : ''));
    }
    exit;
}

if (isset($_GET['deleted'])) {
    if ($_GET['deleted'] === 'forbidden') {
        $alertMsg  = 'Peran Anda tidak memiliki izin menghapus role.';
        $alertType = 'warning';
    } elseif ($_GET['deleted'] === '1') {
        $alertMsg  = 'Role berhasil dihapus.';
        $alertType = 'success';
    } else {
        $alertMsg  = $_GET['msg'] ?? 'Gagal menghapus role.';
        $alertType = 'danger';
    }
}

$roles = $model->getAll();

$pageTitle  = 'Manajemen Role & Akses';
$activePage = 'roles';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Manajemen Role & Akses</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">
            Atur peran (role) dan menu apa saja yang boleh dilihat / ditambah / diubah / dihapus tiap peran,
            sesuai skema akun pada dokumen kebutuhan Si-TANGKAL.
        </p>
    </div>
    <?php if ($canCreateRole): ?>
    <div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahRole">
            <i class="bi bi-plus-lg me-1"></i> Tambah Role
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Alert -->
<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $alertType === 'success' ? 'check-circle' : ($alertType === 'danger' ? 'x-circle' : 'exclamation-triangle') ?> me-2"></i>
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ======= DAFTAR ROLE ======= -->
<div class="row g-3">
    <?php foreach ($roles as $role): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($role['name']) ?></h6>
                        <code style="font-size:0.72rem; color:var(--text-muted);"><?= htmlspecialchars($role['code']) ?></code>
                    </div>
                    <?php if ((int) $role['is_system'] === 1): ?>
                    <span class="badge bg-secondary-subtle text-secondary" title="Peran bawaan dari dokumen kebutuhan">Bawaan</span>
                    <?php else: ?>
                    <span class="badge bg-info-subtle text-info">Custom</span>
                    <?php endif; ?>
                </div>
                <p class="text-muted mb-3" style="font-size:0.8rem; min-height:2.4em;">
                    <?= htmlspecialchars($role['description'] ?: '—') ?>
                </p>
                <div class="d-flex align-items-center justify-content-between">
                    <span style="font-size:0.75rem;" class="text-muted">
                        <i class="bi bi-people me-1"></i><?= (int) $role['total_user'] ?> pengguna
                    </span>
                    <div class="d-flex gap-1">
                        <?php if ($canEditRole): ?>
                        <button type="button" class="btn btn-sm btn-outline-success" title="Atur Izin Menu"
                                data-bs-toggle="modal" data-bs-target="#modalPerm<?= (int) $role['id'] ?>">
                            <i class="bi bi-shield-lock"></i> Izin
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" title="Edit Role"
                                data-bs-toggle="modal" data-bs-target="#modalEditRole<?= (int) $role['id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canDeleteRole && (int) $role['is_system'] === 0): ?>
                        <a href="roles.php?hapus=<?= (int) $role['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus"
                           onclick="return confirm('Yakin ingin menghapus role <?= htmlspecialchars(addslashes($role['name'])) ?>?')">
                            <i class="bi bi-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Edit info role -->
    <div class="modal fade" id="modalEditRole<?= (int) $role['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($role['code']) ?>" disabled>
                        <div class="form-text">Kode tidak bisa diubah setelah dibuat.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($role['name']) ?>" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($role['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_role" class="btn btn-warning text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Matriks Izin Menu -->
    <div class="modal fade" id="modalPerm<?= (int) $role['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" class="modal-content">
                <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-shield-lock text-success me-2"></i>
                        Izin Akses Menu &mdash; <?= htmlspecialchars($role['name']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($role['code'] === 'superadmin'): ?>
                    <div class="alert alert-info py-2" style="font-size:0.8rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Superadmin selalu punya akses penuh ke semua menu (bypass), matriks di bawah hanya catatan &mdash; tidak memengaruhi perilaku sistem.
                    </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th class="text-center" style="width:70px;">Lihat</th>
                                    <th class="text-center" style="width:70px;">Tambah</th>
                                    <th class="text-center" style="width:70px;">Ubah</th>
                                    <th class="text-center" style="width:70px;">Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->getAccessMatrix((int) $role['id']) as $m): ?>
                                <tr>
                                    <td>
                                        <i class="bi <?= htmlspecialchars($m['icon'] ?: 'bi-dot') ?> me-1 text-muted"></i>
                                        <?= htmlspecialchars($m['label']) ?>
                                    </td>
                                    <?php foreach (['view', 'create', 'edit', 'delete'] as $act): ?>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input"
                                               name="perm[<?= (int) $m['menu_id'] ?>][<?= $act ?>]" value="1"
                                               <?= ((int) $m['can_' . $act] === 1) ? 'checked' : '' ?>>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="save_permissions" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Simpan Izin
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php endforeach; ?>
</div>

<!-- ======= MODAL: TAMBAH ROLE ======= -->
<div class="modal fade" id="modalTambahRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-lg text-success me-2"></i>Tambah Role Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" placeholder="mis. staf_kecamatan" required>
                    <div class="form-text">Huruf kecil, tanpa spasi (otomatis diubah ke huruf_bawah).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-1">
                    <label class="form-label">Deskripsi</label>
                    <textarea class="form-control" name="description" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add_role" class="btn btn-success">Simpan Role</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
