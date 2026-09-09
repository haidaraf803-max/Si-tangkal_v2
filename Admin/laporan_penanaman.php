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

// ===== Stok bibit untuk link jenis tanaman & sumber bibit =====
$stokBibitList = [];
try {
    $stokStmt = $config->query("SELECT jenis_tanaman, sumber_bibit FROM stok_bibit ORDER BY jenis_tanaman");
    $stokBibitList = $stokStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $stokBibitList = [];
}
$sumberOptions = array_values(array_unique(array_column($stokBibitList, 'sumber_bibit')));
if (empty($sumberOptions)) {
    $sumberOptions = ['Stok Bibit Dinas', 'Bantuan Pemerintah Pusat/Provinsi', 'CSR/Swasta', 'Swadaya Masyarakat', 'Lainnya'];
}

// ===== CREATE (banyak baris sekaligus) =====
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

        $shared = [
            'tanggal'    => $_POST['tanggal'] ?? date('Y-m-d'),
            'lokasi'     => trim($_POST['lokasi'] ?? ''),
            'latitude'   => trim($_POST['latitude'] ?? ''),
            'longitude'  => trim($_POST['longitude'] ?? ''),
            'keterangan' => trim($_POST['keterangan'] ?? ''),
            'foto'       => $foto,
        ];

        // Baris-baris jenis/sumber/jumlah dari tabel dinamis
        $jenisArr  = $_POST['row_jenis'] ?? [];
        $sumberArr = $_POST['row_sumber'] ?? [];
        $jumlahArr = $_POST['row_jumlah'] ?? [];

        $rows = [];
        $count = max(count($jenisArr), count($sumberArr), count($jumlahArr));
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'jenis_tanaman' => trim($jenisArr[$i] ?? ''),
                'asal_bibit'    => trim($sumberArr[$i] ?? ''),
                'jumlah_bibit'  => (int) ($jumlahArr[$i] ?? 0),
            ];
        }

        $saved = $model->createBatch($rows, $shared, $currentUserId);
        if ($saved > 0) {
            $alertMsg  = $saved > 1 ? "$saved baris laporan penanaman berhasil disimpan." : 'Laporan penanaman berhasil disimpan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Tidak ada baris yang disimpan. Pastikan minimal 1 baris jenis/sumber/jumlah terisi.';
            $alertType = 'danger';
        }
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

// ===== FILTER =====
$keyword   = trim($_GET['cari'] ?? '');
$fTahun    = trim($_GET['tahun'] ?? '');
$fSumber   = trim($_GET['sumber'] ?? '');
$fJenis    = trim($_GET['jenis'] ?? '');

$data = $model->getAll([
    'keyword' => $keyword,
    'tahun'   => $fTahun,
    'sumber'  => $fSumber,
    'jenis'   => $fJenis,
]);
$totalLaporan   = $model->countAll();
$totalBibit     = $model->totalBibitTertanam();
$availableYears = $model->getAvailableYears();
$availableSumber = $model->getAvailableSumber();
$availableJenis  = $model->getAvailableJenis();

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
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.75rem;">Hasil Filter Saat Ini</div>
            <div class="fs-4 fw-bold"><?= count($data) ?> entri</div>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:0.72rem;">Cari</label>
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari lokasi / jenis tanaman / asal bibit..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:0.72rem;">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($availableYears as $th): ?>
                    <option value="<?= htmlspecialchars($th) ?>" <?= $fTahun === $th ? 'selected' : '' ?>><?= htmlspecialchars($th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.72rem;">Sumber Bibit</label>
                <select name="sumber" class="form-select form-select-sm">
                    <option value="">Semua Sumber</option>
                    <?php foreach ($availableSumber as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= $fSumber === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:0.72rem;">Jenis Tanaman</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua Jenis</option>
                    <?php foreach ($availableJenis as $j): ?>
                    <option value="<?= htmlspecialchars($j) ?>" <?= $fJenis === $j ? 'selected' : '' ?>><?= htmlspecialchars($j) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-success"><i class="bi bi-funnel"></i></button>
            </div>
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
    <div class="modal-dialog modal-dialog-centered modal-xl">
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
                        <label class="form-label">Foto Dokumentasi</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
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

                    <div class="col-12">
                        <hr class="my-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0">Detail Penanaman (bisa lebih dari satu baris) <span class="text-danger">*</span></label>
                            <button type="button" id="btnTambahBaris" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Baris
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="tabelBaris">
                                <thead>
                                    <tr>
                                        <th style="width:40%">Jenis Tanaman</th>
                                        <th style="width:35%">Sumber Bibit</th>
                                        <th style="width:20%">Jumlah</th>
                                        <th style="width:5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyBaris">
                                    <!-- baris pertama, template diisi JS juga -->
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted mb-0" style="font-size:0.72rem;">Jenis tanaman &amp; sumber bibit terhubung ke data Stok Bibit yang tersedia. Jika stok tidak tersedia untuk kombinasi tersebut, tetap bisa diketik manual.</p>
                    </div>

                    <div class="col-12">
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

// ===== Data stok bibit untuk dropdown jenis/sumber (dari server) =====
const stokBibitList = <?= json_encode($stokBibitList, JSON_UNESCAPED_UNICODE) ?>;
const daftarJenis  = [...new Set(stokBibitList.map(s => s.jenis_tanaman))].sort();
const daftarSumber = <?= json_encode($sumberOptions, JSON_UNESCAPED_UNICODE) ?>;

function buatOptions(list, selected) {
    let html = '<option value="">-- Pilih / ketik manual --</option>';
    list.forEach(v => {
        html += `<option value="${v.replace(/"/g,'&quot;')}" ${v === selected ? 'selected' : ''}>${v}</option>`;
    });
    return html;
}

function tambahBarisPenanaman() {
    const tbody = document.getElementById('tbodyBaris');
    const idx = tbody.children.length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm mb-1" list="listJenis${idx}" name="row_jenis[]" placeholder="mis. Trembesi, Mahoni">
            <datalist id="listJenis${idx}">${daftarJenis.map(j => `<option value="${j.replace(/"/g,'&quot;')}">`).join('')}</datalist>
        </td>
        <td>
            <select class="form-select form-select-sm" name="row_sumber[]">
                ${buatOptions(daftarSumber, '')}
            </select>
        </td>
        <td>
            <input type="number" min="1" class="form-control form-control-sm" name="row_jumlah[]" placeholder="0">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-baris"><i class="bi bi-trash"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
    tr.querySelector('.btn-hapus-baris').addEventListener('click', function () {
        if (tbody.children.length > 1) { tr.remove(); }
    });
}

document.getElementById('btnTambahBaris').addEventListener('click', tambahBarisPenanaman);
// baris pertama otomatis ada saat modal dimuat
tambahBarisPenanaman();
</script>

<?php require_once 'layouts/footer.php'; ?>