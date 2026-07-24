<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/PengajuanModel.php';

$model         = new PengajuanModel($config);
$statusUpdate  = null;

// ===== CEK ID =====
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

// ===== PROSES UPDATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $namaFileBaru = $data['Dokumentasi'] ?? ''; // Default pakai gambar lama
    $namaFileBaruAfter = $data['DokumentasiAfter'] ?? '';

    // ===== HANDLE UPLOAD FILE SEBELUM =====
    if (isset($_FILES['dokumentasi']) && $_FILES['dokumentasi']['error'] !== UPLOAD_ERR_NO_FILE) {
        $folder   = __DIR__ . '/../images/';
        $allowed  = ['jpg', 'jpeg', 'png', 'gif'];
        $maxSize  = 5 * 1024 * 1024; // 5 MB

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $file    = $_FILES['dokumentasi'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $statusUpdate = 'error';
        } elseif (!in_array($ext, $allowed)) {
            $statusUpdate = 'error_format';
        } elseif ($file['size'] > $maxSize) {
            $statusUpdate = 'error_size';
        } else {
            $newName = uniqid('dok_', true) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $folder . $newName)) {
                // Hapus gambar lama jika ada
                if (!empty($data['Dokumentasi']) && file_exists($folder . $data['Dokumentasi'])) {
                    unlink($folder . $data['Dokumentasi']);
                }
                $namaFileBaru = $newName;
            } else {
                $statusUpdate = 'error';
            }
        }
    }

    // ===== HANDLE UPLOAD FILE SESUDAH =====
    if ($statusUpdate === null && isset($_FILES['dokumentasiAfter']) && $_FILES['dokumentasiAfter']['error'] !== UPLOAD_ERR_NO_FILE) {
        $folder   = __DIR__ . '/../images/';
        $allowed  = ['jpg', 'jpeg', 'png', 'gif'];
        $maxSize  = 5 * 1024 * 1024; // 5 MB

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $file    = $_FILES['dokumentasiAfter'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $statusUpdate = 'error';
        } elseif (!in_array($ext, $allowed)) {
            $statusUpdate = 'error_format';
        } elseif ($file['size'] > $maxSize) {
            $statusUpdate = 'error_size';
        } else {
            $newName = uniqid('dok_after_', true) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $folder . $newName)) {
                // Hapus gambar lama jika ada
                if (!empty($data['DokumentasiAfter']) && file_exists($folder . $data['DokumentasiAfter'])) {
                    unlink($folder . $data['DokumentasiAfter']);
                }
                $namaFileBaruAfter = $newName;
            } else {
                $statusUpdate = 'error';
            }
        }
    }

    // ===== UPDATE DATABASE =====
    if ($statusUpdate === null) {
        $ok = $model->update(
            $id,
            trim($_POST['no_surat']      ?? ''),
            trim($_POST['nama']          ?? ''),
            trim($_POST['nomor_telepon'] ?? ''),
            trim($_POST['lokasi']        ?? ''),
            trim($_POST['disposisi']   ?? ''),
            trim($_POST['survey']      ?? ''),
            trim($_POST['tanggal']     ?? ''),
            trim($_POST['keterangan']  ?? ''),
            $namaFileBaru,
            $namaFileBaruAfter
        );
        $statusUpdate = $ok ? 'success' : 'error';

        // Refresh data setelah update berhasil
        if ($ok) {
            $data = $model->getById($id);
        }
    }
}

$pageTitle  = 'Edit Pengajuan';
$activePage = 'pengajuan';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:0.8rem;">
        <li class="breadcrumb-item"><a href="index.php" class="text-success">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="pengajuan.php" class="text-success">Pengajuan</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">

        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-warning"></i>
                <strong>Edit Data Pengajuan</strong>
                <span class="badge bg-secondary ms-auto">#<?= $id ?></span>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label" for="no_surat">No Surat <span class="text-danger">*</span></label>
                            <input type="text" id="no_surat" name="no_surat" class="form-control"
                                   value="<?= htmlspecialchars($data['No_Surat']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="nama">Nama Pemohon <span class="text-danger">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control"
                                   value="<?= htmlspecialchars($data['Nama_Pemohon']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="nomor_telepon">Nomor Telepon</label>
                            <input type="text" id="nomor_telepon" name="nomor_telepon" class="form-control"
                                   value="<?= htmlspecialchars($data['Nomor_Telepon'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="lokasi">Lokasi Pohon <span class="text-danger">*</span></label>
                            <input type="text" id="lokasi" name="lokasi" class="form-control"
                                   value="<?= htmlspecialchars($data['Lokasi_Pohon']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="disposisi">Disposisi Surat</label>
                            <input type="date" id="disposisi" name="disposisi" class="form-control"
                                   value="<?= htmlspecialchars($data['Disposisi_Surat'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tanggal">Tanggal Penanganan</label>
                            <input type="date" id="tanggal" name="tanggal" class="form-control"
                                   value="<?= htmlspecialchars($data['Tanggal_Penanganan'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="survey">Survey Pohon</label>
                            <textarea id="survey" name="survey" class="form-control" rows="3"
                                      placeholder="Catatan hasil survey..."><?= htmlspecialchars($data['Survey_Pohon'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="keterangan">Status Keterangan <span class="text-danger">*</span></label>
                            <select id="keterangan" name="keterangan" class="form-select" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="Sudah" <?= ($data['Keterangan'] === 'Sudah') ? 'selected' : '' ?>>Sudah</option>
                                <option value="Belum" <?= ($data['Keterangan'] === 'Belum') ? 'selected' : '' ?>>Belum</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-2" style="background:#f8fafc; border:1px solid var(--border-color);">
                                <label class="form-label" for="dokumentasi" style="font-weight:600;">Upload Foto (Sebelum Pemangkasan)</label>
                                <input type="file" id="dokumentasi" name="dokumentasi"
                                       class="form-control" accept=".jpg,.jpeg,.png,.gif">
                                <div class="form-text mb-3">Format: JPG, PNG, GIF. Maks: 5 MB</div>
                                
                                <?php if (!empty($data['Dokumentasi'])): ?>
                                    <label class="form-label text-muted" style="font-size:0.8rem;">Preview Saat Ini:</label>
                                    <div>
                                        <img src="../images/<?= htmlspecialchars($data['Dokumentasi']) ?>"
                                             alt="Sebelum" class="rounded-2 shadow-sm"
                                             style="height:150px; width:100%; object-fit:cover; border:1px solid var(--border-color);">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-2" style="background:#f8fafc; border:1px solid var(--border-color);">
                                <label class="form-label" for="dokumentasiAfter" style="font-weight:600;">Upload Foto (Sesudah Pemangkasan)</label>
                                <input type="file" id="dokumentasiAfter" name="dokumentasiAfter"
                                       class="form-control" accept=".jpg,.jpeg,.png,.gif">
                                <div class="form-text mb-3">Format: JPG, PNG, GIF. Maks: 5 MB</div>

                                <?php if (!empty($data['DokumentasiAfter'])): ?>
                                    <label class="form-label text-muted" style="font-size:0.8rem;">Preview Saat Ini:</label>
                                    <div>
                                        <img src="../images/<?= htmlspecialchars($data['DokumentasiAfter']) ?>"
                                             alt="Sesudah" class="rounded-2 shadow-sm"
                                             style="height:150px; width:100%; object-fit:cover; border:1px solid var(--border-color);">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div><!-- /.row -->

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                        <a href="pengajuan.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        <a href="detail_pengajuan.php?id=<?= $id ?>" class="btn btn-outline-primary ms-auto">
                            <i class="bi bi-eye me-1"></i> Lihat Detail
                        </a>
                    </div>

                </form>
            </div><!-- /.card-body -->
        </div><!-- /.card -->

    </div>
</div>

<!-- SweetAlert2 Feedback -->
<script>
<?php if ($statusUpdate === 'success'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Data pengajuan berhasil diperbarui.',
        confirmButtonColor: '#28a745',
        timer: 2500,
        timerProgressBar: true
    });
});
<?php elseif ($statusUpdate === 'error'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
        confirmButtonColor: '#dc3545'
    });
});
<?php elseif ($statusUpdate === 'error_format'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'warning',
        title: 'Format Tidak Didukung',
        text: 'Hanya file JPG, PNG, dan GIF yang diizinkan.',
        confirmButtonColor: '#fd7e14'
    });
});
<?php elseif ($statusUpdate === 'error_size'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'warning',
        title: 'File Terlalu Besar',
        text: 'Ukuran file maksimal adalah 5 MB.',
        confirmButtonColor: '#fd7e14'
    });
});
<?php endif; ?>
</script>

<?php require_once 'layouts/footer.php'; ?>