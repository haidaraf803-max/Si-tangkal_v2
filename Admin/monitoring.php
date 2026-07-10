<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/MonitoringModel.php';

$model     = new MonitoringModel($config);
$alertMsg  = '';
$alertType = '';

$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    $hapusId = (int) $_GET['hapus'];
    $result  = $model->delete($hapusId);
    header("Location: monitoring.php?deleted=" . ($result['success'] ? '1' : '0'));
    exit;
}

if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] == '1' ? 'Data monitoring berhasil dihapus.' : 'Gagal menghapus data monitoring.';
    $alertType = $_GET['deleted'] == '1' ? 'success' : 'danger';
}

// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {

    $pohon_id             = (int) ($_POST['pohon_id'] ?? 0);
    $tanggal_monitoring   = trim($_POST['tanggal_monitoring'] ?? '');
    $kesehatan_monitoring = trim($_POST['kesehatan_monitoring'] ?? '');
    $catatan              = trim($_POST['catatan'] ?? '');
    $files                = $_FILES['files'] ?? [];
    $detail               = [
        'tinggi_pohon'         => trim($_POST['tinggi_pohon'] ?? ''),
        'diameter_batang'      => trim($_POST['diameter_batang'] ?? ''),
        'lebar_tajuk'          => trim($_POST['lebar_tajuk'] ?? ''),
        'jenis_gangguan'       => trim($_POST['jenis_gangguan'] ?? '') ?: null,
        'tingkat_keparahan'    => trim($_POST['tingkat_keparahan'] ?? '') ?: null,
        'rekomendasi_tindakan' => trim($_POST['rekomendasi_tindakan'] ?? '') ?: null,
        'status_tindak_lanjut' => trim($_POST['status_tindak_lanjut'] ?? '') ?: 'Belum',
        'latitude'             => trim($_POST['latitude'] ?? ''),
        'longitude'            => trim($_POST['longitude'] ?? ''),
    ];

    if ($pohon_id > 0) {
        $result    = $model->create($pohon_id, $currentUserId, $tanggal_monitoring, $kesehatan_monitoring, $catatan, $files, $detail);
        $alertMsg  = $result['message'];
        $alertType = $result['success'] ? 'success' : 'danger';
    } else {
        $alertMsg  = 'Pohon wajib dipilih.';
        $alertType = 'warning';
    }
}

// ===== SEARCH / GET ALL =====
$keyword      = trim($_GET['cari'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$data         = $model->getAll($keyword, $statusFilter);

$totalMonitoring = $model->countAll();
$totalSehat      = $model->countByKesehatan('Sehat');
$totalKurang     = $model->countByKesehatan('Kurang Sehat');
$totalSakit      = $model->countByKesehatan('Sakit');

$pohonList = $model->getPohonOptions();

$pageTitle  = 'Monitoring Pohon';
$activePage = 'monitoring';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Monitoring Pohon</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Kelola riwayat monitoring kondisi pohon beserta dokumentasi foto/video</p>
    </div>
    <div>
        <!-- <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Monitoring
        </button> -->
    </div>
</div>

<!-- Mini Stat Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-clipboard-data text-primary fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalMonitoring ?></div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Total Monitoring</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
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
    <div class="col-6 col-md-3">
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
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:var(--radius-md);">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $totalSakit ?></div>
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
    <div class="card-header bg-white">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <span class="fw-semibold">
                <i class="bi bi-table me-2 text-success"></i>Data Monitoring
                <span class="badge rounded-pill bg-success-subtle text-success ms-1"><?= count($data) ?> data</span>
            </span>
        </div>
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5 col-lg-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="cari"
                           class="form-control border-start-0 ps-0"
                           placeholder="Cari nama pohon, lokasi, kesehatan, atau catatan..."
                           value="<?= htmlspecialchars($keyword) ?>">
                </div>
            </div>
            <div class="col-7 col-md-3 col-lg-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="Belum" <?= $statusFilter === 'Belum' ? 'selected' : '' ?>>Belum</option>
                    <option value="Diproses" <?= $statusFilter === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                    <option value="Selesai" <?= $statusFilter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="col-5 col-md-4 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-success flex-fill flex-md-grow-0">
                    <i class="bi bi-search me-1"></i>Cari
                </button>
                <?php if ($keyword || $statusFilter): ?>
                <a href="monitoring.php" class="btn btn-sm btn-outline-secondary flex-fill flex-md-grow-0" title="Reset filter">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No</th>
                        <th>Tanggal</th>
                        <th>Pohon</th>
                        <th>Lokasi</th>
                        <th class="text-center">Kesehatan</th>
                        <th class="text-center">Status</th>
                        <th>Petugas</th>
                        <th class="text-center">Media</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php $no = 1; foreach ($data as $row):
                            $badgeClass = match ($row['kesehatan_monitoring']) {
                                'Sehat'        => 'bg-success-subtle text-success',
                                'Kurang Sehat' => 'bg-warning-subtle text-warning',
                                'Sakit'        => 'bg-danger-subtle text-danger',
                                default        => 'bg-secondary-subtle text-secondary',
                            };
                            $statusTL = $row['status_tindak_lanjut'] ?? 'Belum';
                            $statusBadge = match ($statusTL) {
                                'Selesai'  => 'bg-success-subtle text-success',
                                'Diproses' => 'bg-warning-subtle text-warning',
                                default    => 'bg-secondary-subtle text-secondary',
                            };
                            $statusIcon = match ($statusTL) {
                                'Selesai'  => 'bi-check-circle',
                                'Diproses' => 'bi-arrow-repeat',
                                default    => 'bi-hourglass-split',
                            };
                        ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                            <td class="text-nowrap"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal_monitoring']))) ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['nama_lokal'] ?? '—') ?></td>
                            <td>
                                <span style="max-width:180px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                      title="<?= htmlspecialchars($row['nama_jalan'] ?? '') ?>">
                                    <?= htmlspecialchars($row['nama_jalan'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($row['kesehatan_monitoring'] ?: '—') ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $statusBadge ?>">
                                    <i class="bi <?= $statusIcon ?> me-1"></i><?= htmlspecialchars($statusTL) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['petugas_name'] ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info">
                                    <i class="bi bi-images me-1"></i><?= (int) $row['media_count'] ?>
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <a href="detail_monitoring.php?id=<?= (int) $row['id'] ?>"
                                   class="btn btn-sm btn-outline-info" title="Detail">
                                    <i class="bi bi-info"></i>
                                </a>
                                <a href="edit_monitoring.php?id=<?= (int) $row['id'] ?>"
                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="monitoring.php?hapus=<?= (int) $row['id'] ?>"
                                   class="btn btn-sm btn-outline-danger" title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus data monitoring ini beserta seluruh medianya?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-clipboard-x fs-1 d-block mb-2 opacity-50"></i>
                                <?php if ($keyword || $statusFilter): ?>
                                    <div>Tidak ditemukan data<?= $keyword ? " untuk \"<strong>" . htmlspecialchars($keyword) . "</strong>\"" : '' ?><?= $statusFilter ? " dengan status <strong>" . htmlspecialchars($statusFilter) . "</strong>" : '' ?></div>
                                    <a href="monitoring.php" class="btn btn-sm btn-outline-secondary mt-2">
                                        <i class="bi bi-x-circle me-1"></i>Reset Pencarian
                                    </a>
                                <?php else: ?>
                                    <div>Belum ada data monitoring</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======= MODAL: TAMBAH MONITORING ======= -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle text-success me-2"></i>Tambah Monitoring Pohon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label" for="pohon_id">Pohon <span class="text-danger">*</span></label>
                            <select id="pohon_id" name="pohon_id" class="form-select" required>
                                <option value="">-- Pilih Pohon --</option>
                                <?php foreach ($pohonList as $p): ?>
                                <option value="<?= (int) $p['id'] ?>">
                                    <?= htmlspecialchars($p['nama_lokal']) ?> — <?= htmlspecialchars($p['nama_jalan']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tanggal_monitoring">Tanggal Monitoring</label>
                            <input type="date" id="tanggal_monitoring" name="tanggal_monitoring" class="form-control"
                                   value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="kesehatan_monitoring">Kondisi Kesehatan</label>
                            <select id="kesehatan_monitoring" name="kesehatan_monitoring" class="form-select">
                                <option value="">-- Pilih Kondisi --</option>
                                <option value="Sehat">Sehat</option>
                                <option value="Kurang Sehat">Kurang Sehat</option>
                                <option value="Sakit">Sakit</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="catatan">Catatan</label>
                            <textarea id="catatan" name="catatan" class="form-control" rows="3"
                                      placeholder="Catatan hasil monitoring (opsional)"></textarea>
                        </div>

                        <div class="col-12"><hr class="my-1"><div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Data Ukur Pohon (opsional)</div></div>

                        <div class="col-md-4">
                            <label class="form-label" for="tinggi_pohon">Tinggi Pohon (meter)</label>
                            <input type="number" step="0.01" min="0" id="tinggi_pohon" name="tinggi_pohon" class="form-control" placeholder="mis. 8.5">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="diameter_batang">Diameter Batang / DBH (cm)</label>
                            <input type="number" step="0.01" min="0" id="diameter_batang" name="diameter_batang" class="form-control" placeholder="mis. 35">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="lebar_tajuk">Lebar Tajuk (meter)</label>
                            <input type="number" step="0.01" min="0" id="lebar_tajuk" name="lebar_tajuk" class="form-control" placeholder="mis. 4.2">
                        </div>

                        <div class="col-12"><hr class="my-1"><div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Gangguan & Tindak Lanjut (opsional)</div></div>

                        <div class="col-md-6">
                            <label class="form-label" for="jenis_gangguan">Jenis Gangguan</label>
                            <select id="jenis_gangguan" name="jenis_gangguan" class="form-select">
                                <option value="">-- Tidak Ada / Tidak Diketahui --</option>
                                <option value="Hama">Hama</option>
                                <option value="Penyakit">Penyakit</option>
                                <option value="Kerusakan Fisik">Kerusakan Fisik</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tingkat_keparahan">Tingkat Keparahan</label>
                            <select id="tingkat_keparahan" name="tingkat_keparahan" class="form-select">
                                <option value="">-- Pilih Tingkat --</option>
                                <option value="Ringan">Ringan</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Berat">Berat</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="rekomendasi_tindakan">Rekomendasi Tindakan</label>
                            <select id="rekomendasi_tindakan" name="rekomendasi_tindakan" class="form-select">
                                <option value="">-- Tidak Ada --</option>
                                <option value="Perlu Pemangkasan">Perlu Pemangkasan</option>
                                <option value="Perlu Penyuntikan/Pengobatan">Perlu Penyuntikan/Pengobatan</option>
                                <option value="Perlu Penyangga">Perlu Penyangga</option>
                                <option value="Ditebang">Ditebang</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="status_tindak_lanjut">Status Tindak Lanjut</label>
                            <select id="status_tindak_lanjut" name="status_tindak_lanjut" class="form-select">
                                <option value="Belum" selected>Belum</option>
                                <option value="Diproses">Diproses</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-1"><div class="text-muted" style="font-size:0.72rem; text-transform:uppercase;">Koordinat GPS Saat Survei (opsional)</div></div>

                        <div class="col-md-6">
                            <label class="form-label" for="latitude">Latitude</label>
                            <input type="number" step="any" id="latitude" name="latitude" class="form-control" placeholder="mis. -6.8872706">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="longitude">Longitude</label>
                            <input type="number" step="any" id="longitude" name="longitude" class="form-control" placeholder="mis. 107.5226141">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="files">Foto / Video Dokumentasi</label>
                            <input type="file" id="files" name="files[]" class="form-control"
                                   accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi,.mkv" multiple>
                            <div class="form-text">Boleh memilih lebih dari satu file (foto dan/atau video) sekaligus.</div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>