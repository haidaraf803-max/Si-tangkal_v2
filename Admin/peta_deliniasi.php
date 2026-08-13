<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'peta_deliniasi', 'view');
$canCreate = Rbac::can($config, 'peta_deliniasi', 'create');
$canDelete = Rbac::can($config, 'peta_deliniasi', 'delete');
require_once 'core/DeliniasiModel.php';

$rthModel     = new DeliniasiRthModel($config);
$tajukModel   = new DeliniasiTajukModel($config);
$potensiModel = new PotensiPenanamanModel($config);

$alertMsg  = '';
$alertType = '';
$currentUserId = (int) ($_SESSION['admin']['UserId'] ?? 0);

// ===== CREATE: Deliniasi RTH =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rth'])) {
    if (!$canCreate) {
        $alertMsg = 'Peran Anda tidak memiliki izin menambah deliniasi RTH.'; $alertType = 'warning';
    } else {
        $ok = $rthModel->create([
            'nama_lokasi' => trim($_POST['nama_lokasi'] ?? ''),
            'jenis_rth'   => trim($_POST['jenis_rth'] ?? ''),
            'kecamatan'   => trim($_POST['kecamatan'] ?? ''),
            'geometry'    => $_POST['geometry'] ?? '[]',
            'keterangan'  => trim($_POST['keterangan'] ?? ''),
        ], $currentUserId);
        $alertMsg  = $ok ? 'Deliniasi RTH berhasil disimpan. Persentase RTH kota diperbarui otomatis.' : 'Gagal menyimpan deliniasi RTH (pastikan poligon digambar minimal 3 titik).';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== CREATE: Deliniasi Tajuk =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tajuk'])) {
    if (!$canCreate) {
        $alertMsg = 'Peran Anda tidak memiliki izin menambah deliniasi tajuk.'; $alertType = 'warning';
    } else {
        $ok = $tajukModel->create([
            'pohon_id'    => $_POST['pohon_id'] ?? null,
            'nama_lokasi' => trim($_POST['nama_lokasi'] ?? ''),
            'geometry'    => $_POST['geometry'] ?? '[]',
            'keterangan'  => trim($_POST['keterangan'] ?? ''),
        ], $currentUserId);
        $alertMsg  = $ok ? 'Deliniasi tajuk pohon berhasil disimpan.' : 'Gagal menyimpan deliniasi tajuk (pastikan poligon digambar minimal 3 titik).';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== CREATE: Penanaman & Potensi =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_potensi'])) {
    if (!$canCreate) {
        $alertMsg = 'Peran Anda tidak memiliki izin menambah data penanaman/potensi.'; $alertType = 'warning';
    } else {
        $ok = $potensiModel->create([
            'tipe'                  => $_POST['tipe'] ?? 'potensi',
            'nama_lokasi'           => trim($_POST['nama_lokasi'] ?? ''),
            'kecamatan'             => trim($_POST['kecamatan'] ?? ''),
            'geometry'              => $_POST['geometry'] ?? '[]',
            'estimasi_jumlah_pohon' => $_POST['estimasi_jumlah_pohon'] ?? '',
            'sumber_kajian'         => trim($_POST['sumber_kajian'] ?? ''),
            'keterangan'            => trim($_POST['keterangan'] ?? ''),
        ], $currentUserId);
        $alertMsg  = $ok ? 'Data penanaman/potensi penanaman berhasil disimpan.' : 'Gagal menyimpan data.';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== DELETE =====
if (isset($_GET['hapus_rth'])) {
    if ($canDelete) { $rthModel->delete((int) $_GET['hapus_rth']); }
    header('Location: peta_deliniasi.php?tab=rth'); exit;
}
if (isset($_GET['hapus_tajuk'])) {
    if ($canDelete) { $tajukModel->delete((int) $_GET['hapus_tajuk']); }
    header('Location: peta_deliniasi.php?tab=tajuk'); exit;
}
if (isset($_GET['hapus_potensi'])) {
    if ($canDelete) { $potensiModel->delete((int) $_GET['hapus_potensi']); }
    header('Location: peta_deliniasi.php?tab=potensi'); exit;
}

$rthList     = $rthModel->getAll();
$tajukList   = $tajukModel->getAll();
$potensiList = $potensiModel->getAll();

$persentaseRth   = $rthModel->persentaseRth();
$totalLuasRth    = $rthModel->totalLuasM2();
$totalLuasTajuk  = $tajukModel->totalLuasM2();
$jumlahRealisasi = $potensiModel->countByTipe('realisasi');
$jumlahPotensi   = $potensiModel->countByTipe('potensi');

$activeTab = $_GET['tab'] ?? 'rth';

$pageTitle  = 'Peta Deliniasi & Potensi';
$activePage = 'peta_deliniasi';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Peta Deliniasi &amp; Potensi Penanaman</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Deliniasi RTH (otomatis hitung persentase), deliniasi tajuk pohon, serta penanaman &amp; potensi penanaman</p>
    </div>
</div>

<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.72rem;">Persentase RTH Kota</div>
            <div class="fs-4 fw-bold text-success"><?= number_format($persentaseRth, 2) ?>%</div>
            <div class="text-muted" style="font-size:0.68rem;">dari luas wilayah <?= number_format(CITY_AREA_M2 / 10000, 0, ',', '.') ?> ha</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.72rem;">Total Luas RTH Terdeliniasi</div>
            <div class="fs-4 fw-bold"><?= number_format($totalLuasRth / 10000, 2, ',', '.') ?> ha</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.72rem;">Total Luas Tajuk Terdeliniasi</div>
            <div class="fs-4 fw-bold"><?= number_format($totalLuasTajuk / 10000, 2, ',', '.') ?> ha</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted" style="font-size:0.72rem;">Realisasi / Potensi Tanam</div>
            <div class="fs-4 fw-bold"><?= $jumlahRealisasi ?> / <?= $jumlahPotensi ?></div>
            <div class="text-muted" style="font-size:0.68rem;">titik/lokasi</div>
        </div></div>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'rth' ? 'active' : '' ?>" href="?tab=rth">Deliniasi RTH</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'tajuk' ? 'active' : '' ?>" href="?tab=tajuk">Deliniasi Tajuk Pohon</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'potensi' ? 'active' : '' ?>" href="?tab=potensi">Penanaman &amp; Potensi Penanaman</a></li>
</ul>

<?php if ($activeTab === 'rth'): ?>
<div class="d-flex justify-content-end mb-2">
    <?php if ($canCreate): ?>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalRth"><i class="bi bi-plus-lg me-1"></i>Tambah Deliniasi RTH</button>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-header">Daftar Deliniasi RTH <span class="badge bg-secondary ms-1"><?= count($rthList) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th class="ps-3">Nama Lokasi</th><th>Jenis RTH</th><th>Kecamatan</th><th class="text-end">Luas (ha)</th><th class="text-center pe-3">Aksi</th></tr></thead>
                <tbody>
                <?php if ($rthList): foreach ($rthList as $r): ?>
                    <tr>
                        <td class="ps-3"><?= htmlspecialchars($r['nama_lokasi']) ?></td>
                        <td><?= htmlspecialchars($r['jenis_rth'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['kecamatan'] ?? '-') ?></td>
                        <td class="text-end"><?= number_format($r['luas_m2'] / 10000, 2, ',', '.') ?></td>
                        <td class="text-center pe-3">
                            <?php if ($canDelete): ?><a href="?hapus_rth=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada deliniasi RTH</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($activeTab === 'tajuk'): ?>
<div class="d-flex justify-content-end mb-2">
    <?php if ($canCreate): ?>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTajuk"><i class="bi bi-plus-lg me-1"></i>Tambah Deliniasi Tajuk</button>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-header">Daftar Deliniasi Tajuk Pohon <span class="badge bg-secondary ms-1"><?= count($tajukList) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th class="ps-3">Nama Lokasi</th><th>Pohon ID</th><th class="text-end">Luas Tajuk (m2)</th><th class="text-center pe-3">Aksi</th></tr></thead>
                <tbody>
                <?php if ($tajukList): foreach ($tajukList as $t): ?>
                    <tr>
                        <td class="ps-3"><?= htmlspecialchars($t['nama_lokasi']) ?></td>
                        <td><?= $t['pohon_id'] ? (int)$t['pohon_id'] : '-' ?></td>
                        <td class="text-end"><?= number_format($t['luas_m2'], 2, ',', '.') ?></td>
                        <td class="text-center pe-3">
                            <?php if ($canDelete): ?><a href="?hapus_tajuk=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada deliniasi tajuk pohon</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($activeTab === 'potensi'): ?>
<div class="d-flex justify-content-end mb-2">
    <?php if ($canCreate): ?>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPotensi"><i class="bi bi-plus-lg me-1"></i>Tambah Titik/Area</button>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-header">Daftar Penanaman &amp; Potensi Penanaman <span class="badge bg-secondary ms-1"><?= count($potensiList) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th class="ps-3">Tipe</th><th>Nama Lokasi</th><th>Kecamatan</th><th class="text-center">Estimasi Pohon</th><th>Sumber Kajian</th><th class="text-center pe-3">Aksi</th></tr></thead>
                <tbody>
                <?php if ($potensiList): foreach ($potensiList as $p): ?>
                    <tr>
                        <td class="ps-3"><span class="badge <?= $p['tipe']==='realisasi' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= ucfirst($p['tipe']) ?></span></td>
                        <td><?= htmlspecialchars($p['nama_lokasi']) ?></td>
                        <td><?= htmlspecialchars($p['kecamatan'] ?? '-') ?></td>
                        <td class="text-center"><?= $p['estimasi_jumlah_pohon'] ?? '-' ?></td>
                        <td style="font-size:0.75rem;" class="text-muted"><?= htmlspecialchars($p['sumber_kajian']) ?></td>
                        <td class="text-center pe-3">
                            <?php if ($canDelete): ?><a href="?hapus_potensi=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data penanaman/potensi</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============ MODAL: Tambah Deliniasi RTH ============ -->
<div class="modal fade" id="modalRth" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Tambah Deliniasi RTH</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2">
          <div class="col-md-6"><label class="form-label">Nama Lokasi *</label><input class="form-control" name="nama_lokasi" required></div>
          <div class="col-md-3"><label class="form-label">Jenis RTH</label>
            <select class="form-select" name="jenis_rth">
              <option value="Taman Kota">Taman Kota</option>
              <option value="Hutan Kota">Hutan Kota</option>
              <option value="Jalur Hijau">Jalur Hijau</option>
              <option value="Pemakaman">Pemakaman</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Kecamatan</label><input class="form-control" name="kecamatan"></div>
        </div>
        <label class="form-label mb-1" style="font-size:0.78rem;">Gambar area RTH di peta (klik untuk menambah titik poligon, min. 3 titik)</label>
        <div id="mapRth" style="height:320px; border-radius:8px;"></div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">Luas otomatis akan dihitung dari poligon</small>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDraw('rth')">Ulangi Gambar</button>
        </div>
        <input type="hidden" name="geometry" id="geomRth">
        <div class="mb-2 mt-2"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="add_rth" class="btn btn-success">Simpan</button></div>
    </form>
  </div>
</div>

<!-- ============ MODAL: Tambah Deliniasi Tajuk ============ -->
<div class="modal fade" id="modalTajuk" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Tambah Deliniasi Tajuk Pohon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2">
          <div class="col-md-8"><label class="form-label">Nama Lokasi *</label><input class="form-control" name="nama_lokasi" required></div>
          <div class="col-md-4"><label class="form-label">ID Pohon (opsional)</label><input type="number" class="form-control" name="pohon_id"></div>
        </div>
        <label class="form-label mb-1" style="font-size:0.78rem;">Gambar area tajuk di peta (klik untuk menambah titik poligon, min. 3 titik)</label>
        <div id="mapTajuk" style="height:320px; border-radius:8px;"></div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">Luas otomatis akan dihitung dari poligon</small>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDraw('tajuk')">Ulangi Gambar</button>
        </div>
        <input type="hidden" name="geometry" id="geomTajuk">
        <div class="mb-2 mt-2"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="add_tajuk" class="btn btn-success">Simpan</button></div>
    </form>
  </div>
</div>

<!-- ============ MODAL: Tambah Penanaman/Potensi ============ -->
<div class="modal fade" id="modalPotensi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Tambah Titik Penanaman / Potensi Penanaman</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2">
          <div class="col-md-3"><label class="form-label">Tipe *</label>
            <select class="form-select" name="tipe" required>
              <option value="potensi">Potensi Penanaman</option>
              <option value="realisasi">Realisasi Penanaman</option>
            </select>
          </div>
          <div class="col-md-5"><label class="form-label">Nama Lokasi *</label><input class="form-control" name="nama_lokasi" required></div>
          <div class="col-md-4"><label class="form-label">Kecamatan</label><input class="form-control" name="kecamatan"></div>
        </div>
        <label class="form-label mb-1" style="font-size:0.78rem;">Klik peta 1x untuk titik lokasi, atau beberapa kali untuk area potensi (poligon)</label>
        <div id="mapPotensi" style="height:320px; border-radius:8px;"></div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">1 titik = lokasi tunggal, ≥3 titik = area potensi (luas otomatis)</small>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDraw('potensi')">Ulangi Gambar</button>
        </div>
        <input type="hidden" name="geometry" id="geomPotensi">
        <div class="row g-2 mt-2">
          <div class="col-md-4"><label class="form-label">Estimasi Jumlah Pohon</label><input type="number" class="form-control" name="estimasi_jumlah_pohon"></div>
          <div class="col-md-8"><label class="form-label">Sumber Kajian</label>
            <input class="form-control" name="sumber_kajian" value="Kajian Potensi Penanaman Kota Cimahi 2026 (Anggaran Perubahan)">
          </div>
        </div>
        <div class="mb-1 mt-2"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="add_potensi" class="btn btn-success">Simpan</button></div>
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const CIMAHI = [-6.8743, 107.5425];
const drawState = {}; // { key: { map, points:[], layer, markers:[] } }

function initDrawMap(key, elId) {
    if (drawState[key]) { drawState[key].map.invalidateSize(); return; }
    const map = L.map(elId).setView(CIMAHI, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    const state = { map, points: [], layer: null, markers: [] };
    drawState[key] = state;

    map.on('click', function (e) {
        state.points.push([e.latlng.lat, e.latlng.lng]);
        const m = L.circleMarker(e.latlng, { radius: 5, color: '#059669' }).addTo(map);
        state.markers.push(m);
        redrawPolygon(key);
        document.getElementById('geom' + capitalize(key)).value = JSON.stringify(state.points);
    });
}

function redrawPolygon(key) {
    const state = drawState[key];
    if (state.layer) { state.map.removeLayer(state.layer); state.layer = null; }
    if (state.points.length >= 2) {
        state.layer = L.polygon(state.points, { color: '#059669', fillOpacity: 0.25 }).addTo(state.map);
    }
}

function resetDraw(key) {
    const state = drawState[key];
    if (!state) return;
    state.points = [];
    state.markers.forEach(m => state.map.removeLayer(m));
    state.markers = [];
    if (state.layer) { state.map.removeLayer(state.layer); state.layer = null; }
    document.getElementById('geom' + capitalize(key)).value = '[]';
}

function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

document.getElementById('modalRth')?.addEventListener('shown.bs.modal', () => initDrawMap('rth', 'mapRth'));
document.getElementById('modalTajuk')?.addEventListener('shown.bs.modal', () => initDrawMap('tajuk', 'mapTajuk'));
document.getElementById('modalPotensi')?.addEventListener('shown.bs.modal', () => initDrawMap('potensi', 'mapPotensi'));
</script>

<?php require_once 'layouts/footer.php'; ?>
