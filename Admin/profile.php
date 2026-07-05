<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');

$id = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== AMBIL DATA USER =====
$stmt = $config->prepare("SELECT * FROM t_users WHERE UserId = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    Auth::logout();
    header("Location: ../login.php");
    exit;
}

$success = '';
$error   = '';

// ===== UPDATE PROFILE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    try {
        $username = htmlspecialchars(strip_tags(trim($_POST['username'] ?? '')));
        $name     = htmlspecialchars(strip_tags(trim($_POST['name'] ?? '')));
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

        if (!$username || !$name) {
            $error = 'Username dan nama lengkap tidak boleh kosong.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            $sql  = "UPDATE t_users SET Username = :username, Name = :name, Email = :email WHERE UserId = :id";
            $stmt = $config->prepare($sql);
            $ok   = $stmt->execute([
                ':username' => $username,
                ':name'     => $name,
                ':email'    => $email,
                ':id'       => $id,
            ]);

            if ($ok) {
                // Update session
                $_SESSION['admin']['Name']     = $name;
                $_SESSION['admin']['Username'] = $username;
                $success = 'Profil berhasil diperbarui!';

                // Refresh data
                $stmtRef = $config->prepare("SELECT * FROM t_users WHERE UserId = ?");
                $stmtRef->execute([$id]);
                $data = $stmtRef->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Gagal memperbarui profil.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan pada database.';
    }
}

$pageTitle  = 'Profil Admin';
$activePage = 'profile';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-7">

        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-circle me-2 text-success"></i>
                <strong>Profil Admin</strong>
            </div>
            <div class="card-body p-4">

                <!-- Avatar + Info -->
                <div class="d-flex align-items-center gap-4 mb-4 pb-4" style="border-bottom:1px solid var(--border-color);">
                    <div style="
                        width:80px; height:80px; border-radius:50%;
                        background:linear-gradient(135deg, #28a745, #063d1e);
                        display:flex; align-items:center; justify-content:center;
                        color:#fff; font-weight:700; font-size:2rem; flex-shrink:0;
                    ">
                        <?= strtoupper(substr($data['Name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($data['Name']) ?></h5>
                        <div class="text-muted" style="font-size:0.82rem;">
                            <i class="bi bi-shield-check me-1 text-success"></i><?= htmlspecialchars($data['Type']) ?>
                        </div>
                        <div class="text-muted" style="font-size:0.78rem; margin-top:2px;">
                            <i class="bi bi-calendar3 me-1"></i>Bergabung: <?= htmlspecialchars($data['CreatedDate'] ?? '-') ?>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Form Update -->
                <form method="POST">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color:var(--border-color);">
                                    <i class="bi bi-person text-muted"></i>
                                </span>
                                <input type="text" id="username" name="username" class="form-control"
                                       value="<?= htmlspecialchars($data['Username']) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color:var(--border-color);">
                                    <i class="bi bi-person-badge text-muted"></i>
                                </span>
                                <input type="text" id="name" name="name" class="form-control"
                                       value="<?= htmlspecialchars($data['Name']) ?>" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color:var(--border-color);">
                                    <i class="bi bi-envelope text-muted"></i>
                                </span>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($data['Email']) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['Type']) ?>" readonly
                                   style="background:#f8fafc; color:var(--text-muted);">
                        </div>

                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" name="update" class="btn btn-success">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </form>

            </div><!-- /.card-body -->
        </div><!-- /.card -->

    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
