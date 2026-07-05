<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/PohonModel.php';

$model     = new PohonModel($config);
$alertMsg  = '';
$alertType = '';

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    $hapusId = (int) $_GET['hapus'];
    if ($model->delete($hapusId)) {
        header("Location: pohon.php?deleted=1");
    } else {
        header("Location: pohon.php?deleted=0");
    }
    exit;
}

// Alert dari redirect
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] == '1' ? 'Data pohon berhasil dihapus.' : 'Gagal menghapus data pohon.';
    $alertType = $_GET['deleted'] == '1' ? 'success' : 'danger';
}

// ===== CREATE =====
// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {

    $nama_lokal   = trim($_POST['nama_lokal'] ?? '');
    $nama_latin   = trim($_POST['nama_latin'] ?? '');
    $family       = trim($_POST['family'] ?? '');
    $kesehatan    = trim($_POST['kesehatan'] ?? '');

    $tahun_tanam  = trim($_POST['tahun_tanam'] ?? '');
    $habitus      = trim($_POST['habitus'] ?? '');
    $status_kel   = trim($_POST['status_kel'] ?? '');
    $volume       = trim($_POST['volume'] ?? '');

    $kelas_awet   = trim($_POST['kelas_awet'] ?? '');
    $kelas_kuat   = trim($_POST['kelas_kuat'] ?? '');

    $berat_jenis  = trim($_POST['berat_jenis'] ?? '');
    $serapan_co   = trim($_POST['serapan_co'] ?? '');
    $produksi_o   = trim($_POST['produksi_o'] ?? '');

    $nama_jalan   = trim($_POST['nama_jalan'] ?? '');
    $kelurahan    = trim($_POST['kelurahan'] ?? '');
    $kecamatan    = trim($_POST['kecamatan'] ?? '');

    // Catatan: form pakai name="latitude" & name="longitude".
    // koordinat_x = longitude (X), koordinat_y = latitude (Y).
    $koordinat_x  = trim($_POST['longitude'] ?? '');
    $koordinat_y  = trim($_POST['latitude'] ?? '');

    $keterangan   = trim($_POST['keterangan'] ?? '');

    if ($nama_lokal != '' && $kesehatan != '' && $nama_jalan != '' && $koordinat_x !== '' && $koordinat_y !== '') {

        if (
            $model->create(
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
            )
        ) {
            $alertMsg  = 'Data pohon berhasil ditambahkan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Gagal menambahkan data pohon.';
            $alertType = 'danger';
        }

    } else {

        $alertMsg  = 'Nama lokal, kondisi kesehatan, nama jalan, latitude, dan longitude wajib diisi.';
        $alertType = 'warning';

    }

}

// ===== SEARCH / GET ALL =====
$keyword = trim($_GET['cari'] ?? '');
$data    = $keyword ? $model->search($keyword) : $model->getAll();

// Hitung ringkasan kondisi
$totalSehat     = $model->countByKondisi('Sehat');
$totalKurang    = $model->countByKondisi('Kurang Sehat');
$totalMati      = $model->countByKondisi('Sakit');

$pageTitle  = 'Kondisi Pohon';
$activePage = 'pohon';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Kondisi Pohon</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Kelola dan pantau kondisi pohon di Kota Cimahi</p>
    </div>
    <div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pohon
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-arrow-repeat me-1"></i> Sync To C-Map
        </button>
    </div>
</div>

<!-- Mini Stat Row -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-tree-fill text-success fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalSehat ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Sehat</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-tree text-warning fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalKurang ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Kurang Sehat</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalMati ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Sakit</div>
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
        <span><i class="bi bi-table me-2"></i>Data Pohon
            <span class="badge bg-secondary ms-1"><?= count($data) ?></span>
        </span>
        <form method="GET" class="d-flex gap-2" style="min-width:240px;">
            <input type="text" name="cari" class="form-control form-control-sm"
                   placeholder="Cari nama / lokasi / kesehatan..."
                   value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-search"></i></button>
            <?php if ($keyword): ?>
            <a href="pohon.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No</th>
                        <th>Nama Lokal</th>
                        <th>Nama Latin</th>
                        <th>Family</th>
                        <th class="text-center">Kondisi</th>
                        <th>Lokasi</th>
                        <th>Keterangan</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php $no = 1; foreach ($data as $row):
                            $kondisi = $row['kesehatan'];
                            $badgeClass = match ($kondisi) {
                                'Sehat'        => 'bg-success-subtle text-success',
                                'Kurang Sehat'  => 'bg-warning-subtle text-warning',
                                'Sakit'         => 'bg-danger-subtle text-danger',
                                default        => 'bg-secondary-subtle text-secondary',
                            };
                        ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['nama_lokal']) ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['nama_latin']) ?></td>
                            <td><?= htmlspecialchars($row['family']) ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($kondisi) ?></span>
                            </td>
                            <td>
                                <span style="max-width:200px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                      title="<?= htmlspecialchars($row['nama_jalan']) ?>">
                                    <?= htmlspecialchars($row['nama_jalan']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="max-width:160px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                      title="<?= htmlspecialchars($row['keterangan']) ?>">
                                    <?= htmlspecialchars($row['keterangan'] ?: '—') ?>
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <a href="detail_pohon.php?id=<?= (int)$row['id'] ?>"
                                   class="btn btn-sm btn-outline-info"
                                   title="Detail"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-info"></i>
                                </a>
                                <a href="edit_pohon.php?id=<?= (int)$row['id'] ?>"
                                   class="btn btn-sm btn-outline-warning"
                                   title="Edit"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="pohon.php?hapus=<?= (int)$row['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Hapus"
                                   onclick="event.stopPropagation(); return confirm('Yakin ingin menghapus data pohon ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-tree fs-2 d-block mb-2"></i>
                                <?= $keyword ? "Tidak ditemukan data untuk \"<strong>" . htmlspecialchars($keyword) . "</strong>\"" : 'Belum ada data pohon' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======= MODAL: TAMBAH POHON ======= -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:var(--radius-lg); border:none; box-shadow:var(--shadow-lg);">
            <form method="POST">

                <input type="hidden" name="action" value="create">

                <div class="modal-header" style="border-bottom:1px solid var(--border-color);">
                    <h5 class="modal-title fw-bold" id="modalTambahLabel">
                        <i class="bi bi-tree me-2 text-success"></i>
                        Tambah Data Pohon
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body py-3">

                    <div class="row g-3">

                        <!-- Nama Lokal -->
                        <div class="col-md-6">
                            <label class="form-label" for="nama_lokal">
                                Nama Lokal <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="nama_lokal"
                                name="nama_lokal"
                                class="form-control"
                                placeholder="Contoh: Pohon Mangga"
                                required>
                        </div>

                        <!-- Nama Latin -->
                        <div class="col-md-6">
                            <label class="form-label" for="nama_latin">
                                Nama Latin
                            </label>

                            <input
                                type="text"
                                id="nama_latin"
                                name="nama_latin"
                                class="form-control"
                                placeholder="Contoh: Mangifera indica">
                        </div>

                        <!-- Family -->
                        <div class="col-md-6">
                            <label class="form-label" for="family">
                                Family
                            </label>

                            <input
                                type="text"
                                id="family"
                                name="family"
                                class="form-control"
                                placeholder="Contoh: Moraceae">
                        </div>

                        <!-- Kondisi -->
                        <div class="col-md-6">
                            <label class="form-label" for="kesehatan">
                                Kondisi Kesehatan <span class="text-danger">*</span>
                            </label>

                            <select
                                id="kesehatan"
                                name="kesehatan"
                                class="form-select"
                                required>

                                <option value="">-- Pilih Kondisi --</option>
                                <option value="Sehat">Sehat</option>
                                <option value="Kurang Sehat">Kurang Sehat</option>
                                <option value="Sakit">Sakit</option>

                            </select>
                        </div>

                        <!-- Tahun Tanam -->
                        <div class="col-md-6">
                            <label class="form-label" for="tahun_tanam">
                                Tahun Tanam
                            </label>

                            <input
                                type="number"
                                id="tahun_tanam"
                                name="tahun_tanam"
                                class="form-control"
                                placeholder="Contoh: 2020">
                        </div>

                        <!-- Habitus -->
                        <div class="col-md-6">
                            <label class="form-label" for="habitus">
                                Habitus
                            </label>

                            <input
                                type="text"
                                id="habitus"
                                name="habitus"
                                class="form-control"
                                placeholder="Masukkan habitus pohon">
                        </div>

                        <!-- Status Kelompok -->
                        <div class="col-md-6">
                            <label class="form-label" for="status_kel">
                                Status Kelompok
                            </label>

                            <input
                                type="text"
                                id="status_kel"
                                name="status_kel"
                                class="form-control"
                                placeholder="Contoh: Least Concern">
                        </div>

                        <!-- Volume -->
                        <div class="col-md-6">
                            <label class="form-label" for="volume">
                                Volume
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                id="volume"
                                name="volume"
                                class="form-control"
                                placeholder="Contoh: 0.2310123">
                        </div>

                        <!-- Kelas Awet -->
                        <div class="col-md-4">
                            <label class="form-label" for="kelas_awet">
                                Kelas Awet
                            </label>

                            <select
                                id="kelas_awet"
                                name="kelas_awet"
                                class="form-select">

                                <option value="">-- Pilih Kelas --</option>
                                <option value="I">I</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>

                            </select>
                        </div>

                        <!-- Kelas Kuat -->
                        <div class="col-md-4">
                            <label class="form-label" for="kelas_kuat">
                                Kelas Kuat
                            </label>

                            <select
                                id="kelas_kuat"
                                name="kelas_kuat"
                                class="form-select">

                                <option value="">-- Pilih Kelas --</option>
                                <option value="I">I</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>

                            </select>
                        </div>

                        <!-- Berat Jenis -->
                        <div class="col-md-4">
                            <label class="form-label" for="berat_jenis">
                                Berat Jenis
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                id="berat_jenis"
                                name="berat_jenis"
                                class="form-control"
                                placeholder="Contoh: 310.0123">
                        </div>

                        <!-- Serapan CO -->
                        <div class="col-md-6">
                            <label class="form-label" for="serapan_co">
                                Serapan CO₂
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                id="serapan_co"
                                name="serapan_co"
                                class="form-control"
                                placeholder="Contoh: 1124.1234503">
                        </div>

                        <!-- Produksi O -->
                        <div class="col-md-6">
                            <label class="form-label" for="produksi_o">
                                Produksi O₂
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                id="produksi_o"
                                name="produksi_o"
                                class="form-control"
                                placeholder="Contoh: 1000.012343">
                        </div>

                        <!-- Nama Jalan -->
                        <div class="col-md-12">
                            <label class="form-label" for="nama_jalan">
                                Nama Jalan <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="nama_jalan"
                                name="nama_jalan"
                                class="form-control"
                                placeholder="Alamat lokasi pohon"
                                required>
                        </div>

                        <!-- Kelurahan -->
                        <div class="col-md-6">
                            <label class="form-label" for="kelurahan">
                                Kelurahan
                            </label>

                            <input
                                type="text"
                                id="kelurahan"
                                name="kelurahan"
                                class="form-control"
                                placeholder="Kelurahan lokasi pohon">
                        </div>

                        <!-- Kecamatan -->
                        <div class="col-md-6">
                            <label class="form-label" for="kecamatan">
                                Kecamatan
                            </label>

                            <input
                                type="text"
                                id="kecamatan"
                                name="kecamatan"
                                class="form-control"
                                placeholder="Kecamatan lokasi pohon">
                        </div>

                        <!-- Latitude -->
                        <div class="col-md-6">
                            <label class="form-label" for="latitude">
                                Latitude (Y) <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="latitude"
                                name="latitude"
                                class="form-control"
                                placeholder="-6.88912345"
                                required>
                        </div>

                        <!-- Longitude -->
                        <div class="col-md-6">
                            <label class="form-label" for="longitude">
                                Longitude (X) <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="longitude"
                                name="longitude"
                                class="form-control"
                                placeholder="107.54123456"
                                required>
                        </div>

                        <!-- Keterangan -->
                        <div class="col-12">
                            <label class="form-label" for="keterangan">
                                Keterangan
                            </label>

                            <textarea
                                id="keterangan"
                                name="keterangan"
                                class="form-control"
                                rows="2"
                                placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer" style="border-top:1px solid var(--border-color);">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success">
                        <i class="bi bi-save me-1"></i>
                        Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
