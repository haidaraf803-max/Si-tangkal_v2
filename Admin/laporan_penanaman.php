<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'laporan_penanaman', 'view');
$canCreate = Rbac::can($config, 'laporan_penanaman', 'create');
$canEdit   = Rbac::can($config, 'laporan_penanaman', 'edit');
$canDelete = Rbac::can($config, 'laporan_penanaman', 'delete');
require_once 'core/PenanamanModel.php';

$model     = new LaporanPenanamanModel($config);
$alertMsg  = '';
$alertType = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== CREATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!$canCreate) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menambah laporan penanaman.';
        $alertType = 'warning';
    } else {
        $foto = null;
        if (!empty($_FILES['foto']['name'])) {
            $foto = 'tanam_' . time() . '_' . basename($_FILES['foto']['name']);
            move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/../assets/foto/' . $foto);
        }
        $ok = $model->create([
            'tanggal'       => $_POST['tanggal'] ?? date('Y-m-d'),
            'lokasi'        => trim($_POST['lokasi'] ?? ''),
            'latitude'      => trim($_POST['latitude'] ?? ''),
            'longitude'     => trim($_POST['longitude'] ?? ''),
            'asal_bibit'    => trim($_POST['asal_bibit'] ?? ''),
            'jenis_tanaman' => trim($_POST['jenis_tanaman'] ?? ''),
            'jumlah_bibit'  => $_POST['jumlah_bibit'] ?? 0,
            'keterangan'    => trim($_POST['keterangan'] ?? ''),
            'foto'          => $foto,
        ], $currentUserId);
        $alertMsg  = $ok ? 'Laporan penanaman berhasil disimpan.' : 'Gagal menyimpan laporan penanaman.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== DELETE =====
if (isset($_GET['hapus'])) {
    if (!$canDelete) { header('Location: laporan_penanaman.php?deleted=forbidden'); exit; }
    $model->delete((int) $_GET['hapus']);
    header('Location: laporan_penanaman.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) {
    $alertMsg  = $_GET['deleted'] === 'forbidden' ? 'Peran Anda tidak memiliki izin menghapus data.' : 'Data berhasil dihapus.';
    $alertType = $_GET['deleted'] === 'forbidden' ? 'warning' : 'success';
}

$keyword = trim($_GET['cari'] ?? '');
$data    = $model->getAll($keyword);
$totalLaporan = $model->countAll();
$totalBibit   = $model->totalBibitTertanam();

$pageTitle  = 'Laporan Penanaman';
$activePage = 'laporan_penanaman';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Laporan Penanaman</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Realisasi penanaman pohon/tanaman di lapangan (Petugas Penanaman)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php
            $exportModul        = 'laporan_penanaman';
            $exportLabel        = 'Laporan Penanaman';
            $exportSupportsDate = true;
            require 'layouts/export_modal.php';
        ?>
        <?php if ($canCreate): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Laporan
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.75rem;">Total Laporan</div>
            <div class="fs-4 fw-bold"><?= $totalLaporan ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.75rem;">Total Bibit Tertanam</div>
            <div class="fs-4 fw-bold"><?= number_format($totalBibit, 0, ',', '.') ?> batang</div>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari lokasi / jenis tanaman / asal bibit..." value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-sm btn-outline-success"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Riwayat Laporan Penanaman <span class="badge bg-secondary ms-1"><?= count($data) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Lokasi</th>
                        <th>Koordinat</th>
                        <th>Asal Bibit</th>
                        <th>Jenis</th>
                        <th class="text-center">Jumlah</th>
                        <th>Petugas</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))) ?></td>
                            <td><?= htmlspecialchars($row['lokasi']) ?></td>
                            <td class="text-muted" style="font-size:0.78rem;">
                                <?php if ($row['latitude'] && $row['longitude']): ?>
                                    <a href="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>" target="_blank">
                                        <?= $row['latitude'] ?>, <?= $row['longitude'] ?>
                                    </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['asal_bibit']) ?></td>
                            <td><?= htmlspecialchars($row['jenis_tanaman'] ?? '-') ?></td>
                            <td class="text-center"><?= (int) $row['jumlah_bibit'] ?></td>
                            <td><?= htmlspecialchars($row['petugas_nama'] ?? '—') ?></td>
                            <td class="text-center pe-3">
                                <?php if ($canDelete): ?>
                                <a href="laporan_penanaman.php?hapus=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus laporan ini?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">Belum ada laporan penanaman</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tree-fill text-success me-2"></i>Tambah Laporan Penanaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Tanaman</label>
                        <input type="text" class="form-control" name="jenis_tanaman" placeholder="mis. Trembesi, Mahoni">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Lokasi Penanaman <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lokasi" placeholder="Alamat / nama lokasi" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Latitude (koordinat)</label>
                        <input type="text" class="form-control" name="latitude" id="latInput" placeholder="-6.8743">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitude (koordinat)</label>
                        <input type="text" class="form-control" name="longitude" id="lngInput" placeholder="107.5425">
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-1" style="font-size:0.78rem;">Klik peta untuk mengisi koordinat otomatis</label>
                        <div id="pickerMap" style="height:220px; border-radius:8px; overflow:hidden;"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Asal Bibit <span class="text-danger">*</span></label>
                        <select class="form-select" name="asal_bibit" required>
                            <option value="">-- Pilih --</option>
                            <option value="Stok Bibit Dinas">Stok Bibit Dinas</option>
                            <option value="Bantuan Pemerintah Pusat/Provinsi">Bantuan Pemerintah Pusat/Provinsi</option>
                            <option value="CSR/Swasta">CSR / Swasta</option>
                            <option value="Swadaya Masyarakat">Swadaya Masyarakat</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Bibit <span class="text-danger">*</span></label>
                        <input type="number" min="1" class="form-control" name="jumlah_bibit" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Foto Dokumentasi</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="1"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.getElementById('modalTambah').addEventListener('shown.bs.modal', function () {
    if (window._pickerMapInit) { window._pickerMap.invalidateSize(); return; }
    window._pickerMapInit = true;
    const map = L.map('pickerMap').setView([-6.8743, 107.5425], 13);
    window._pickerMap = map;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    let marker = null;
    map.on('click', function (e) {
        document.getElementById('latInput').value = e.latlng.lat.toFixed(7);
        document.getElementById('lngInput').value = e.latlng.lng.toFixed(7);
        if (marker) { marker.setLatLng(e.latlng); } else { marker = L.marker(e.latlng).addTo(map); }
    });
});
</script>

<?php require_once 'layouts/footer.php'; ?>
