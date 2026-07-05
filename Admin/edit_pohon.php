<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/PohonModel.php';

$model        = new PohonModel($config);
$statusUpdate = null;

// ===== CEK ID =====
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

// ===== PROSES UPDATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lokal   = trim($_POST['nama_lokal']   ?? '');
    $nama_latin   = trim($_POST['nama_latin']   ?? '');
    $family       = trim($_POST['family']       ?? '');
    $tahun_tanam  = trim($_POST['tahun_tanam']  ?? '');
    $habitus      = trim($_POST['habitus']      ?? '');
    $status_kel   = trim($_POST['status_kel']   ?? '');
    $volume       = trim($_POST['volume']       ?? '');
    $kelas_awet   = trim($_POST['kelas_awet']   ?? '');
    $kelas_kuat   = trim($_POST['kelas_kuat']   ?? '');
    $berat_jenis  = trim($_POST['berat_jenis']  ?? '');
    $kesehatan    = trim($_POST['kondisi_pohon'] ?? '');
    $serapan_co   = trim($_POST['serapan_co']   ?? '');
    $produksi_o   = trim($_POST['produksi_o']   ?? '');
    $nama_jalan   = trim($_POST['nama_jalan']   ?? '');
    $kelurahan    = trim($_POST['kelurahan']    ?? '');
    $kecamatan    = trim($_POST['kecamatan']    ?? '');
    // koordinat_x = longitude (X), koordinat_y = latitude (Y)
    $koordinat_x  = trim($_POST['longitude']    ?? '');
    $koordinat_y  = trim($_POST['latitude']     ?? '');
    $keterangan   = trim($_POST['keterangan']   ?? '');

    $allowedKondisi = ['Sehat', 'Kurang Sehat', 'Sakit'];

    if ($nama_lokal !== '' && in_array($kesehatan, $allowedKondisi, true) && $koordinat_x !== '' && $koordinat_y !== '') {
        $ok = $model->update(
            $id,
            $nama_lokal,
            $nama_latin,
            $family,
            $tahun_tanam,
            $habitus,
            $status_kel,
            (float) $volume,
            $kelas_awet,
            $kelas_kuat,
            (float) $berat_jenis,
            $kesehatan,
            (float) $serapan_co,
            (float) $produksi_o,
            $nama_jalan,
            $kelurahan,
            $kecamatan,
            (float) $koordinat_x,
            (float) $koordinat_y,
            $keterangan
        );
        $statusUpdate = $ok ? 'success' : 'error';
        if ($ok) {
            $data = $model->getById($id); // Refresh data
        }
    } else {
        $statusUpdate = 'error_validation';
    }
}

$pageTitle  = 'Edit Pohon';
$activePage = 'pohon';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:0.8rem;">
        <li class="breadcrumb-item"><a href="index.php" class="text-success">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="pohon.php" class="text-success">Kondisi Pohon</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-12 col-xl-8">

        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-warning"></i>
                <strong>Edit Data Pohon</strong>
                <span class="badge bg-secondary ms-auto">#<?= $id ?></span>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label" for="nama_lokal">Nama Lokal <span class="text-danger">*</span></label>
                            <input type="text" id="nama_lokal" name="nama_lokal" class="form-control"
                                   value="<?= htmlspecialchars($data['nama_lokal']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="nama_latin">Jenis Latin </label>
                            <input type="text" id="nama_latin" name="nama_latin" class="form-control"
                                   value="<?= htmlspecialchars($data['nama_latin']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="family">Family </label>
                            <input type="text" id="family" name="family" class="form-control"
                            value="<?= htmlspecialchars($data['family']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="kondisi_pohon">Kondisi <span class="text-danger">*</span></label>
                            <select id="kondisi_pohon" name="kondisi_pohon" class="form-select" required>
                                <option value="">-- Pilih Kondisi --</option>
                                <option value="Sehat"        <?= ($data['kesehatan'] === 'Sehat')        ? 'selected' : '' ?>>Sehat</option>
                                <option value="Kurang Sehat" <?= ($data['kesehatan'] === 'Kurang Sehat') ? 'selected' : '' ?>>Kurang Sehat</option>
                                <option value="Sakit"        <?= ($data['kesehatan'] === 'Sakit')        ? 'selected' : '' ?>>Sakit</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tahun_tanam">Tahun Tanam </label>
                            <input type="text" id="tahun_tanam" name="tahun_tanam" class="form-control"
                                value="<?= htmlspecialchars($data['tahun_tanam']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="habitus">Habitus </label>
                            <input type="text" id="habitus" name="habitus" class="form-control"
                                value="<?= htmlspecialchars($data['habitus']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="status_kel">Status Kelompok </label>
                            <input type="text" id="status_kel" name="status_kel" class="form-control"
                            value="<?= htmlspecialchars($data['status_kel']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="volume">Volume </label>
                            <input type="number" step="0.000001" id="volume" name="volume" class="form-control"
                            value="<?= htmlspecialchars($data['volume']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="kelas_awet">Kelas Awet </label>
                            <select id="kelas_awet" name="kelas_awet" class="form-select">
                                <option value="">-- Pilih Kelas --</option>
                                <option value="I"   <?= ($data['kelas_awet'] === 'I')   ? 'selected' : '' ?>>I</option>
                                <option value="II"  <?= ($data['kelas_awet'] === 'II')  ? 'selected' : '' ?>>II</option>
                                <option value="III" <?= ($data['kelas_awet'] === 'III') ? 'selected' : '' ?>>III</option>
                                <option value="IV"  <?= ($data['kelas_awet'] === 'IV')  ? 'selected' : '' ?>>IV</option>
                                <option value="V"   <?= ($data['kelas_awet'] === 'V')   ? 'selected' : '' ?>>V</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="kelas_kuat">Kelas Kuat </label>
                            <select id="kelas_kuat" name="kelas_kuat" class="form-select">
                                <option value="">-- Pilih Kelas --</option>
                                <option value="I"   <?= ($data['kelas_kuat'] === 'I')   ? 'selected' : '' ?>>I</option>
                                <option value="II"  <?= ($data['kelas_kuat'] === 'II')  ? 'selected' : '' ?>>II</option>
                                <option value="III" <?= ($data['kelas_kuat'] === 'III') ? 'selected' : '' ?>>III</option>
                                <option value="IV"  <?= ($data['kelas_kuat'] === 'IV')  ? 'selected' : '' ?>>IV</option>
                                <option value="V"   <?= ($data['kelas_kuat'] === 'V')   ? 'selected' : '' ?>>V</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="berat_jenis">Berat Jenis </label>
                            <input type="number" step="0.000001" id="berat_jenis" name="berat_jenis" class="form-control"
                            value="<?= htmlspecialchars($data['berat_jenis']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="serapan_co">Serapan CO2 </label>
                            <input type="number" step="0.000001" id="serapan_co" name="serapan_co" class="form-control"
                            value="<?= htmlspecialchars($data['serapan_co']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="produksi_o">Produksi O2 </label>
                            <input type="number" step="0.000001" id="produksi_o" name="produksi_o" class="form-control"
                            value="<?= htmlspecialchars($data['produksi_o']) ?>">
                        </div>
                        <!--<div class="col-md-6">
                            <label class="form-label">Preview Kondisi</label>
                            <div id="kondisiBadgePreview" class="p-2 rounded-2" style="border:1px solid var(--border-color); background:#f8fafc; min-height:38px; display:flex; align-items:center;">
                                <?php
                                    $bc = match($data['kesehatan']) {
                                        'Sehat' => 'bg-success-subtle text-success',
                                        'Kurang Sehat' => 'bg-warning-subtle text-warning',
                                        'Sakit' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary'
                                    };
                                ?>
                                <span class="badge rounded-pill <?= $bc ?>" id="kondisiBadge">
                                    <?= htmlspecialchars($data['kesehatan']) ?>
                                </span>
                            </div>
                        </div>-->

                        <div class="col-12">
                            <label class="form-label" for="lokasi">Nama Jalan </label>
                            <input type="text" id="nama_jalan" name="nama_jalan" class="form-control"
                                   value="<?= htmlspecialchars($data['nama_jalan']) ?>">
                        </div>

                        <div class="col-6">
                            <label class="form-label" for="lokasi">Kecamatan </label>
                            <input type="text" id="kecamatan" name="kecamatan" class="form-control"
                                   value="<?= htmlspecialchars($data['kecamatan']) ?>">
                        </div>

                        <div class="col-6">
                            <label class="form-label" for="lokasi">Kelurahan </label>
                            <input type="text" id="kelurahan" name="kelurahan" class="form-control"
                                   value="<?= htmlspecialchars($data['kelurahan']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="latitude">Latitude (Y) <span class="text-danger">*</span></label>
                            <input type="text" id="latitude" name="latitude" class="form-control"
                            value="<?= htmlspecialchars($data['koordinat_y']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="longitude">Longitude (X) <span class="text-danger">*</span></label>
                            <input type="text" id="longitude" name="longitude" class="form-control"
                            value="<?= htmlspecialchars($data['koordinat_x']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="keterangan">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" class="form-control" rows="3"
                                      placeholder="Catatan tambahan..."><?= htmlspecialchars($data['keterangan'] ?? '') ?></textarea>
                        </div>

                    </div><!-- /.row -->

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                        <a href="pohon.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script>
<?php if ($statusUpdate === 'success'): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Data pohon berhasil diperbarui.',
        confirmButtonColor: '#28a745',
        timer: 2500,
        timerProgressBar: true
    });
});
<?php elseif ($statusUpdate === 'error'): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Terjadi kesalahan saat menyimpan data.',
        confirmButtonColor: '#dc3545'
    });
});
<?php elseif ($statusUpdate === 'error_validation'): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'warning',
        title: 'Data Tidak Lengkap',
        text: 'Nama lokal, kondisi kesehatan, latitude, dan longitude wajib diisi.',
        confirmButtonColor: '#fd7e14'
    });
});
<?php endif; ?>
</script>

<?php require_once 'layouts/footer.php'; ?>
