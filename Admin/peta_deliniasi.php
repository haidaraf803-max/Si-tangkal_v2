<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'peta_deliniasi', 'view');
$canCreate = Rbac::can($config, 'peta_deliniasi', 'create');
$canEdit   = Rbac::can($config, 'peta_deliniasi', 'edit');
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

// ===== UPDATE: Deliniasi RTH =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_rth'])) {
    $editId = (int) $_POST['edit_rth'];
    if (!$canEdit) {
        $alertMsg = 'Peran Anda tidak memiliki izin mengubah deliniasi RTH.'; $alertType = 'warning';
    } else {
        $ok = $rthModel->update($editId, [
            'nama_lokasi' => trim($_POST['nama_lokasi'] ?? ''),
            'jenis_rth'   => trim($_POST['jenis_rth'] ?? ''),
            'kecamatan'   => trim($_POST['kecamatan'] ?? ''),
            'geometry'    => $_POST['geometry'] ?? '[]',
            'keterangan'  => trim($_POST['keterangan'] ?? ''),
        ]);
        $alertMsg  = $ok ? 'Deliniasi RTH berhasil diperbarui. Persentase RTH kota diperbarui otomatis.' : 'Gagal memperbarui deliniasi RTH (pastikan poligon digambar minimal 3 titik).';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== UPDATE: Deliniasi Tajuk =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_tajuk'])) {
    $editId = (int) $_POST['edit_tajuk'];
    if (!$canEdit) {
        $alertMsg = 'Peran Anda tidak memiliki izin mengubah deliniasi tajuk.'; $alertType = 'warning';
    } else {
        $ok = $tajukModel->update($editId, [
            'pohon_id'    => $_POST['pohon_id'] ?? null,
            'nama_lokasi' => trim($_POST['nama_lokasi'] ?? ''),
            'geometry'    => $_POST['geometry'] ?? '[]',
            'keterangan'  => trim($_POST['keterangan'] ?? ''),
        ]);
        $alertMsg  = $ok ? 'Deliniasi tajuk pohon berhasil diperbarui.' : 'Gagal memperbarui deliniasi tajuk (pastikan poligon digambar minimal 3 titik).';
        $alertType = $ok ? 'success' : 'danger';
    }
}

// ===== UPDATE: Penanaman & Potensi =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_potensi'])) {
    $editId = (int) $_POST['edit_potensi'];
    if (!$canEdit) {
        $alertMsg = 'Peran Anda tidak memiliki izin mengubah data penanaman/potensi.'; $alertType = 'warning';
    } else {
        $ok = $potensiModel->update($editId, [
            'tipe'                  => $_POST['tipe'] ?? 'potensi',
            'nama_lokasi'           => trim($_POST['nama_lokasi'] ?? ''),
            'kecamatan'             => trim($_POST['kecamatan'] ?? ''),
            'geometry'              => $_POST['geometry'] ?? '[]',
            'estimasi_jumlah_pohon' => $_POST['estimasi_jumlah_pohon'] ?? '',
            'sumber_kajian'         => trim($_POST['sumber_kajian'] ?? ''),
            'keterangan'            => trim($_POST['keterangan'] ?? ''),
        ]);
        $alertMsg  = $ok ? 'Data penanaman/potensi penanaman berhasil diperbarui.' : 'Gagal memperbarui data.';
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
    <div class="col-md-3 d-flex">
        <div class="card w-100"><div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:34px; height:34px; border-radius:8px; background:rgba(25,135,84,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="bi bi-percent text-success"></i>
                </div>
                <div class="text-muted" style="font-size:0.72rem;">Persentase RTH Kota</div>
            </div>
            <div class="fs-4 fw-bold text-success mb-1"><?= number_format($persentaseRth, 2) ?>%</div>
            <div class="text-muted mt-auto" style="font-size:0.68rem;">dari luas wilayah <?= number_format(CITY_AREA_M2 / 10000, 0, ',', '.') ?> ha</div>
        </div></div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="card w-100"><div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:34px; height:34px; border-radius:8px; background:rgba(13,110,253,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="bi bi-tree text-primary"></i>
                </div>
                <div class="text-muted" style="font-size:0.72rem;">Total Luas RTH Terdeliniasi</div>
            </div>
            <div class="fs-4 fw-bold mb-1"><?= number_format($totalLuasRth / 10000, 2, ',', '.') ?> ha</div>
            <div class="text-muted mt-auto" style="font-size:0.68rem;">total area terdata di peta</div>
        </div></div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="card w-100"><div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:34px; height:34px; border-radius:8px; background:rgba(255,193,7,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="bi bi-tree-fill text-warning"></i>
                </div>
                <div class="text-muted" style="font-size:0.72rem;">Total Luas Tajuk Terdeliniasi</div>
            </div>
            <div class="fs-4 fw-bold mb-1"><?= number_format($totalLuasTajuk / 10000, 2, ',', '.') ?> ha</div>
            <div class="text-muted mt-auto" style="font-size:0.68rem;">total tutupan tajuk pohon</div>
        </div></div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="card w-100"><div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:34px; height:34px; border-radius:8px; background:rgba(13,202,240,0.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="bi bi-flower1 text-info"></i>
                </div>
                <div class="text-muted" style="font-size:0.72rem;">Realisasi / Potensi Tanam</div>
            </div>
            <div class="fs-4 fw-bold mb-1"><?= $jumlahRealisasi ?> / <?= $jumlahPotensi ?></div>
            <div class="text-muted mt-auto" style="font-size:0.68rem;">titik/lokasi</div>
        </div></div>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'rth' ? 'active' : '' ?>" href="?tab=rth">Deliniasi RTH</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'tajuk' ? 'active' : '' ?>" href="?tab=tajuk">Deliniasi Tajuk Pohon</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'potensi' ? 'active' : '' ?>" href="?tab=potensi">Penanaman &amp; Potensi Penanaman</a></li>
</ul>

<?php if ($activeTab === 'rth'): ?>
<div class="d-flex justify-content-end mb-2 gap-2">
    <?php if ($rthList): ?>
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalViewAllRth"><i class="bi bi-map me-1"></i>Lihat Semua di Peta</button>
    <?php endif; ?>
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
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#viewRth<?= (int)$r['id'] ?>" title="Lihat"><i class="bi bi-eye"></i></button>
                            <?php if ($canEdit): ?><button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editRth<?= (int)$r['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></button><?php endif; ?>
                            <?php if ($canDelete): ?><a href="?hapus_rth=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')" title="Hapus"><i class="bi bi-trash"></i></a><?php endif; ?>
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
<div class="d-flex justify-content-end mb-2 gap-2">
    <?php if ($tajukList): ?>
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalViewAllTajuk"><i class="bi bi-map me-1"></i>Lihat Semua di Peta</button>
    <?php endif; ?>
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
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#viewTajuk<?= (int)$t['id'] ?>" title="Lihat"><i class="bi bi-eye"></i></button>
                            <?php if ($canEdit): ?><button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editTajuk<?= (int)$t['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></button><?php endif; ?>
                            <?php if ($canDelete): ?><a href="?hapus_tajuk=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')" title="Hapus"><i class="bi bi-trash"></i></a><?php endif; ?>
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
<div class="d-flex justify-content-end mb-2 gap-2">
    <?php if ($potensiList): ?>
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalViewAllPotensi"><i class="bi bi-map me-1"></i>Lihat Semua di Peta</button>
    <?php endif; ?>
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
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#viewPotensi<?= (int)$p['id'] ?>" title="Lihat"><i class="bi bi-eye"></i></button>
                            <?php if ($canEdit): ?><button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editPotensi<?= (int)$p['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></button><?php endif; ?>
                            <?php if ($canDelete): ?><a href="?hapus_potensi=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')" title="Hapus"><i class="bi bi-trash"></i></a><?php endif; ?>
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
<div class="modal fade" id="modalRth" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
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
        <label class="form-label mb-1" style="font-size:0.78rem;">Gunakan tool <i class="bi bi-hexagon"></i> pada toolbar peta untuk menggambar poligon area RTH. Titik dapat digeser/diedit setelah digambar.</label>
        <div id="mapRth" class="map-digitasi"></div>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted">Estimasi luas: <span id="luasPreviewRth" class="fw-semibold text-success">- ha</span></small>
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
<div class="modal fade" id="modalTajuk" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Tambah Deliniasi Tajuk Pohon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2">
          <div class="col-md-8"><label class="form-label">Nama Lokasi *</label><input class="form-control" name="nama_lokasi" required></div>
          <div class="col-md-4"><label class="form-label">ID Pohon (opsional)</label><input type="number" class="form-control" name="pohon_id"></div>
        </div>
        <label class="form-label mb-1" style="font-size:0.78rem;">Gunakan tool <i class="bi bi-hexagon"></i> pada toolbar peta untuk menggambar poligon area tajuk. Titik dapat digeser/diedit setelah digambar.</label>
        <div id="mapTajuk" class="map-digitasi"></div>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted">Estimasi luas: <span id="luasPreviewTajuk" class="fw-semibold text-success">- m2</span></small>
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
<div class="modal fade" id="modalPotensi" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
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
        <label class="form-label mb-1" style="font-size:0.78rem;">Gunakan tool <i class="bi bi-geo-alt"></i> untuk 1 titik lokasi, atau tool <i class="bi bi-hexagon"></i> untuk area potensi (poligon).</label>
        <div id="mapPotensi" class="map-digitasi"></div>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted"><span id="luasPreviewPotensi">Belum ada geometri digambar</span></small>
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

<!-- ============ MODAL: Lihat Semua Deliniasi RTH di Peta ============ -->
<div class="modal fade" id="modalViewAllRth" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-map me-1"></i>Semua Deliniasi RTH di Peta <span class="badge bg-secondary ms-1"><?= count($rthList) ?></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div id="mapViewAllRth" class="map-view-all" data-viewall-items='<?= htmlspecialchars(json_encode(array_map(function ($r) {
            return [
                'geometry' => json_decode($r['geometry'], true) ?: [],
                'type'     => 'polygon',
                'color'    => '#198754',
                'popup'    => htmlspecialchars($r['nama_lokasi'], ENT_QUOTES)
                    . '<br><small>' . htmlspecialchars($r['jenis_rth'] ?? '-', ENT_QUOTES) . ' &middot; '
                    . number_format($r['luas_m2'] / 10000, 2, ',', '.') . ' ha</small>',
            ];
        }, $rthList)), ENT_QUOTES) ?>'></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<!-- ============ MODAL: Lihat Semua Deliniasi Tajuk di Peta ============ -->
<div class="modal fade" id="modalViewAllTajuk" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-map me-1"></i>Semua Deliniasi Tajuk Pohon di Peta <span class="badge bg-secondary ms-1"><?= count($tajukList) ?></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div id="mapViewAllTajuk" class="map-view-all" data-viewall-items='<?= htmlspecialchars(json_encode(array_map(function ($t) {
            return [
                'geometry' => json_decode($t['geometry'], true) ?: [],
                'type'     => 'polygon',
                'color'    => '#ffc107',
                'popup'    => htmlspecialchars($t['nama_lokasi'], ENT_QUOTES)
                    . '<br><small>' . number_format($t['luas_m2'], 2, ',', '.') . ' m2</small>',
            ];
        }, $tajukList)), ENT_QUOTES) ?>'></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<!-- ============ MODAL: Lihat Semua Penanaman & Potensi di Peta ============ -->
<div class="modal fade" id="modalViewAllPotensi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-map me-1"></i>Semua Penanaman &amp; Potensi Penanaman di Peta <span class="badge bg-secondary ms-1"><?= count($potensiList) ?></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2" style="font-size:0.75rem;">
            <span class="badge bg-success me-1">&nbsp;</span> Realisasi Penanaman
            <span class="badge bg-warning text-dark ms-3 me-1">&nbsp;</span> Potensi Penanaman
        </div>
        <div id="mapViewAllPotensi" class="map-view-all" data-viewall-items='<?= htmlspecialchars(json_encode(array_map(function ($p) {
            $points = json_decode($p['geometry'], true) ?: [];
            return [
                'geometry' => $points,
                'type'     => count($points) === 1 ? 'marker' : 'polygon',
                'color'    => $p['tipe'] === 'realisasi' ? '#198754' : '#ffc107',
                'popup'    => htmlspecialchars($p['nama_lokasi'], ENT_QUOTES)
                    . '<br><small>' . htmlspecialchars(ucfirst($p['tipe']), ENT_QUOTES)
                    . ($p['estimasi_jumlah_pohon'] ? ' &middot; ' . (int) $p['estimasi_jumlah_pohon'] . ' pohon' : '') . '</small>',
            ];
        }, $potensiList)), ENT_QUOTES) ?>'></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<?php foreach ($rthList as $r): ?>
<!-- ============ MODAL: Lihat Deliniasi RTH #<?= (int)$r['id'] ?> ============ -->
<div class="modal fade" id="viewRth<?= (int)$r['id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye me-1"></i><?= htmlspecialchars($r['nama_lokasi']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2" style="font-size:0.85rem;">
          <div class="col-md-4"><strong>Jenis RTH:</strong> <?= htmlspecialchars($r['jenis_rth'] ?? '-') ?></div>
          <div class="col-md-4"><strong>Kecamatan:</strong> <?= htmlspecialchars($r['kecamatan'] ?? '-') ?></div>
          <div class="col-md-4"><strong>Luas:</strong> <?= number_format($r['luas_m2'] / 10000, 2, ',', '.') ?> ha</div>
        </div>
        <?php if (!empty($r['keterangan'])): ?><div class="mb-2" style="font-size:0.85rem;"><strong>Keterangan:</strong> <?= nl2br(htmlspecialchars($r['keterangan'])) ?></div><?php endif; ?>
        <div id="mapViewRth<?= (int)$r['id'] ?>" class="map-view-only" data-view-geometry='<?= htmlspecialchars($r['geometry'], ENT_QUOTES) ?>' data-view-type="polygon" data-view-color="#198754"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<!-- ============ MODAL: Edit Deliniasi RTH #<?= (int)$r['id'] ?> ============ -->
<div class="modal fade" id="editRth<?= (int)$r['id'] ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Deliniasi RTH</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2">
          <div class="col-md-6"><label class="form-label">Nama Lokasi *</label><input class="form-control" name="nama_lokasi" required value="<?= htmlspecialchars($r['nama_lokasi']) ?>"></div>
          <div class="col-md-3"><label class="form-label">Jenis RTH</label>
            <select class="form-select" name="jenis_rth">
              <?php foreach (['Taman Kota', 'Hutan Kota', 'Jalur Hijau', 'Pemakaman', 'Lainnya'] as $opt): ?>
              <option value="<?= $opt ?>" <?= $r['jenis_rth'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Kecamatan</label><input class="form-control" name="kecamatan" value="<?= htmlspecialchars($r['kecamatan'] ?? '') ?>"></div>
        </div>
        <label class="form-label mb-1" style="font-size:0.78rem;">Poligon area RTH yang tersimpan sudah dimuat di peta. Geser vertex atau gambar ulang jika perlu diperbarui.</label>
        <div id="mapEditRth<?= (int)$r['id'] ?>" class="map-digitasi" data-edit-geometry='<?= htmlspecialchars($r['geometry'], ENT_QUOTES) ?>' data-edit-type="polygon"></div>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted">Estimasi luas: <span id="luasPreviewEditRth<?= (int)$r['id'] ?>" class="fw-semibold text-success">- ha</span></small>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDraw('editRth<?= (int)$r['id'] ?>')">Ulangi Gambar</button>
        </div>
        <input type="hidden" name="geometry" id="geomEditRth<?= (int)$r['id'] ?>">
        <div class="mb-2 mt-2"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"><?= htmlspecialchars($r['keterangan'] ?? '') ?></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit_rth" value="<?= (int)$r['id'] ?>" class="btn btn-primary">Simpan Perubahan</button></div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php foreach ($tajukList as $t): ?>
<!-- ============ MODAL: Lihat Deliniasi Tajuk #<?= (int)$t['id'] ?> ============ -->
<div class="modal fade" id="viewTajuk<?= (int)$t['id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye me-1"></i><?= htmlspecialchars($t['nama_lokasi']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2" style="font-size:0.85rem;">
          <div class="col-md-6"><strong>Pohon ID:</strong> <?= $t['pohon_id'] ? (int)$t['pohon_id'] : '-' ?></div>
          <div class="col-md-6"><strong>Luas Tajuk:</strong> <?= number_format($t['luas_m2'], 2, ',', '.') ?> m2</div>
        </div>
        <?php if (!empty($t['keterangan'])): ?><div class="mb-2" style="font-size:0.85rem;"><strong>Keterangan:</strong> <?= nl2br(htmlspecialchars($t['keterangan'])) ?></div><?php endif; ?>
        <div id="mapViewTajuk<?= (int)$t['id'] ?>" class="map-view-only" data-view-geometry='<?= htmlspecialchars($t['geometry'], ENT_QUOTES) ?>' data-view-type="polygon" data-view-color="#ffc107"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<!-- ============ MODAL: Edit Deliniasi Tajuk #<?= (int)$t['id'] ?> ============ -->
<div class="modal fade" id="editTajuk<?= (int)$t['id'] ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Deliniasi Tajuk Pohon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2">
          <div class="col-md-8"><label class="form-label">Nama Lokasi *</label><input class="form-control" name="nama_lokasi" required value="<?= htmlspecialchars($t['nama_lokasi']) ?>"></div>
          <div class="col-md-4"><label class="form-label">ID Pohon (opsional)</label><input type="number" class="form-control" name="pohon_id" value="<?= $t['pohon_id'] ? (int)$t['pohon_id'] : '' ?>"></div>
        </div>
        <label class="form-label mb-1" style="font-size:0.78rem;">Poligon area tajuk yang tersimpan sudah dimuat di peta. Geser vertex atau gambar ulang jika perlu diperbarui.</label>
        <div id="mapEditTajuk<?= (int)$t['id'] ?>" class="map-digitasi" data-edit-geometry='<?= htmlspecialchars($t['geometry'], ENT_QUOTES) ?>' data-edit-type="polygon"></div>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted">Estimasi luas: <span id="luasPreviewEditTajuk<?= (int)$t['id'] ?>" class="fw-semibold text-success">- m2</span></small>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDraw('editTajuk<?= (int)$t['id'] ?>')">Ulangi Gambar</button>
        </div>
        <input type="hidden" name="geometry" id="geomEditTajuk<?= (int)$t['id'] ?>">
        <div class="mb-2 mt-2"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"><?= htmlspecialchars($t['keterangan'] ?? '') ?></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit_tajuk" value="<?= (int)$t['id'] ?>" class="btn btn-primary">Simpan Perubahan</button></div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php foreach ($potensiList as $p): $pPoints = json_decode($p['geometry'], true) ?: []; $pType = count($pPoints) === 1 ? 'marker' : 'polygon'; ?>
<!-- ============ MODAL: Lihat Potensi/Penanaman #<?= (int)$p['id'] ?> ============ -->
<div class="modal fade" id="viewPotensi<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye me-1"></i><?= htmlspecialchars($p['nama_lokasi']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2" style="font-size:0.85rem;">
          <div class="col-md-4"><strong>Tipe:</strong> <span class="badge <?= $p['tipe']==='realisasi' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= ucfirst($p['tipe']) ?></span></div>
          <div class="col-md-4"><strong>Kecamatan:</strong> <?= htmlspecialchars($p['kecamatan'] ?? '-') ?></div>
          <div class="col-md-4"><strong>Estimasi Pohon:</strong> <?= $p['estimasi_jumlah_pohon'] ?? '-' ?></div>
        </div>
        <div class="mb-2" style="font-size:0.85rem;"><strong>Sumber Kajian:</strong> <?= htmlspecialchars($p['sumber_kajian']) ?></div>
        <?php if (!empty($p['keterangan'])): ?><div class="mb-2" style="font-size:0.85rem;"><strong>Keterangan:</strong> <?= nl2br(htmlspecialchars($p['keterangan'])) ?></div><?php endif; ?>
        <div id="mapViewPotensi<?= (int)$p['id'] ?>" class="map-view-only" data-view-geometry='<?= htmlspecialchars($p['geometry'], ENT_QUOTES) ?>' data-view-type="<?= $pType ?>" data-view-color="<?= $p['tipe']==='realisasi' ? '#198754' : '#ffc107' ?>"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<!-- ============ MODAL: Edit Potensi/Penanaman #<?= (int)$p['id'] ?> ============ -->
<div class="modal fade" id="editPotensi<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Titik Penanaman / Potensi Penanaman</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2 mb-2">
          <div class="col-md-3"><label class="form-label">Tipe *</label>
            <select class="form-select" name="tipe" required>
              <option value="potensi" <?= $p['tipe']==='potensi' ? 'selected' : '' ?>>Potensi Penanaman</option>
              <option value="realisasi" <?= $p['tipe']==='realisasi' ? 'selected' : '' ?>>Realisasi Penanaman</option>
            </select>
          </div>
          <div class="col-md-5"><label class="form-label">Nama Lokasi *</label><input class="form-control" name="nama_lokasi" required value="<?= htmlspecialchars($p['nama_lokasi']) ?>"></div>
          <div class="col-md-4"><label class="form-label">Kecamatan</label><input class="form-control" name="kecamatan" value="<?= htmlspecialchars($p['kecamatan'] ?? '') ?>"></div>
        </div>
        <label class="form-label mb-1" style="font-size:0.78rem;">Titik/area yang tersimpan sudah dimuat di peta. Geser atau gambar ulang jika perlu diperbarui.</label>
        <div id="mapEditPotensi<?= (int)$p['id'] ?>" class="map-digitasi" data-edit-geometry='<?= htmlspecialchars($p['geometry'], ENT_QUOTES) ?>' data-edit-type="<?= $pType ?>"></div>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted"><span id="luasPreviewEditPotensi<?= (int)$p['id'] ?>">Belum ada geometri digambar</span></small>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDraw('editPotensi<?= (int)$p['id'] ?>')">Ulangi Gambar</button>
        </div>
        <input type="hidden" name="geometry" id="geomEditPotensi<?= (int)$p['id'] ?>">
        <div class="row g-2 mt-2">
          <div class="col-md-4"><label class="form-label">Estimasi Jumlah Pohon</label><input type="number" class="form-control" name="estimasi_jumlah_pohon" value="<?= $p['estimasi_jumlah_pohon'] ?? '' ?>"></div>
          <div class="col-md-8"><label class="form-label">Sumber Kajian</label><input class="form-control" name="sumber_kajian" value="<?= htmlspecialchars($p['sumber_kajian']) ?>"></div>
        </div>
        <div class="mb-1 mt-2"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"><?= htmlspecialchars($p['keterangan'] ?? '') ?></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit_potensi" value="<?= (int)$p['id'] ?>" class="btn btn-primary">Simpan Perubahan</button></div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.19.2/dist/leaflet-geoman.css" />
<script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.19.2/dist/leaflet-geoman.min.js"></script>
<!-- DataService: sumber layer WMS GeoServer (Foto Udara, Batas Administrasi, RTH, dll)
     — sudah dipakai juga oleh assets/js/map.js, dipakai ulang di sini supaya
     konfigurasi endpoint GeoServer tetap satu tempat (tidak duplikat). -->
<script src="../assets/js/data-service.js"></script>
<style>
/* Toolbar Geoman menyesuaikan warna tema hijau aplikasi */
.leaflet-pm-toolbar .leaflet-pm-icon-polygon,
.leaflet-pm-toolbar .leaflet-pm-icon-marker { filter: none; }
/* Panel toggle layer referensi (Foto Udara / Batas Administrasi / RTH) */
.reference-layer-control {
    background: #fff;
    border-radius: 8px;
    font-size: 0.72rem;
    line-height: 1.7;
    box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    overflow: hidden;
    min-width: 160px;
}
.reference-layer-control .ref-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 6px 10px;
    cursor: pointer;
    user-select: none;
}
.reference-layer-control .ref-header:hover { background: #f4f6f5; }
.reference-layer-control .ref-title {
    font-size: 0.66rem;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.reference-layer-control .ref-chevron {
    font-size: 0.7rem;
    color: #667;
    transition: transform 0.2s ease;
    flex-shrink: 0;
}
.reference-layer-control.collapsed .ref-chevron { transform: rotate(-90deg); }
.reference-layer-control .ref-body {
    padding: 4px 10px 8px;
    border-top: 1px solid #eee;
}
.reference-layer-control.collapsed .ref-body { display: none; }
.reference-layer-control label {
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    margin-bottom: 0;
    white-space: nowrap;
}
/* Peta digitasi — dibuat responsif terhadap tinggi layar (bukan angka
   fix 320px) supaya nyaman dipakai menggambar poligon di layar besar
   maupun kecil. min-height jaga supaya tetap layak di layar pendek. */
.map-digitasi {
    height: 65vh;
    min-height: 380px;
    max-height: 720px;
    border-radius: 8px;
}
@media (max-width: 991.98px) {
    /* Modal full-screen di layar <lg (lihat modal-fullscreen-lg-down) —
       peta boleh lebih tinggi lagi karena tidak dibatasi modal-dialog */
    .map-digitasi { height: 70vh; }
}
/* Peta pada modal "Lihat" (read-only, satu geometri) */
.map-view-only {
    height: 45vh;
    min-height: 280px;
    max-height: 520px;
    border-radius: 8px;
}
/* Peta pada modal "Lihat Semua di Peta" (read-only, seluruh baris tabel) */
.map-view-all {
    height: 65vh;
    min-height: 380px;
    max-height: 720px;
    border-radius: 8px;
}
@media (max-width: 991.98px) {
    .map-view-all { height: 70vh; }
}
</style>
<script>
const CIMAHI = [-6.8743, 107.5425];
// key -> { map, layer (poligon/marker aktif), mode: 'polygon'|'marker' }
const drawState = {};
// key -> { fotoudara: L.Layer|null, administrasi: L.Layer|null, rth: L.Layer|null }
// Cache supaya WMS layer tidak di-fetch ulang tiap kali checkbox dicentang.
const refLayerCache = {};
// elementId -> instance L.Map, untuk peta read-only pada modal "Lihat" & "Lihat Semua di Peta"
const viewMapCache = {};

/**
 * Panel kecil di pojok kanan-atas peta digitasi untuk mengaktifkan layer
 * referensi dari GeoServer: Foto Udara, Batas Administrasi, dan RTH.
 * Semua layer TIDAK aktif secara default (baru di-fetch saat dicentang)
 * supaya modal tetap ringan/cepat dibuka.
 */
function addReferenceLayerControl(map, key) {
    const control = L.control({ position: 'topright' });

    control.onAdd = function () {
        const div = L.DomUtil.create('div', 'reference-layer-control');
        div.innerHTML = `
            <div class="ref-header" data-ref-toggle>
                <span class="ref-title">Layer Referensi</span>
                <i class="bi bi-chevron-down ref-chevron"></i>
            </div>
            <div class="ref-body">
                <label><input type="checkbox" data-ref-layer="fotoudara"> Foto Udara</label>
                <label><input type="checkbox" data-ref-layer="administrasi"> Batas Administrasi</label>
                <label><input type="checkbox" data-ref-layer="rth"> RTH</label>
            </div>
        `;
        // Cegah klik/scroll di panel ini ikut menggerakkan/menggambar di peta
        L.DomEvent.disableClickPropagation(div);
        L.DomEvent.disableScrollPropagation(div);

        // Chevron: klik judul panel untuk buka/tutup daftar checkbox layer referensi
        div.querySelector('[data-ref-toggle]').addEventListener('click', () => {
            div.classList.toggle('collapsed');
        });

        div.querySelectorAll('input[data-ref-layer]').forEach((cb) => {
            cb.addEventListener('change', () => {
                toggleReferenceLayer(map, key, cb.dataset.refLayer, cb.checked, cb);
            });
        });

        return div;
    };

    control.addTo(map);
}

async function toggleReferenceLayer(map, key, layerKey, checked, checkboxEl) {
    if (!refLayerCache[key]) refLayerCache[key] = {};
    const cache = refLayerCache[key];

    if (!checked) {
        if (cache[layerKey]) map.removeLayer(cache[layerKey]);
        return;
    }

    try {
        if (!cache[layerKey]) {
            if (checkboxEl) checkboxEl.disabled = true; // cegah klik ganda saat masih memuat
            if (layerKey === 'fotoudara') {
                cache[layerKey] = await DataService.getFotoudara();
            } else if (layerKey === 'administrasi') {
                cache[layerKey] = await DataService.getDistricts();
            } else if (layerKey === 'rth') {
                cache[layerKey] = await DataService.getGreenSpaces();
            }
        }
        cache[layerKey].addTo(map);
    } catch (e) {
        console.error('Gagal memuat layer referensi: ' + layerKey, e);
        if (checkboxEl) checkboxEl.checked = false;
    } finally {
        if (checkboxEl) checkboxEl.disabled = false;
    }
}

/**
 * FIX BUG: modal Bootstrap tertutup sendiri saat proses menggambar
 * (mis. setelah menutup poligon dengan klik titik awal, atau saat Geoman
 * membatalkan mode gambar). Alih-alih menebak dan memblokir event mana
 * yang jadi biang keroknya, cara paling pasti adalah MENAHAN LANGSUNG
 * proses penutupan modal itu sendiri: modal hanya boleh tertutup jika
 * pemicunya benar-benar tombol "Batal" / ikon "X" (data-bs-dismiss).
 * Selain itu, event hide.bs.modal apa pun sumbernya akan dibatalkan.
 */
function setupModalCloseGuard(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) return;

    let allowClose = false;

    modalEl.addEventListener('hide.bs.modal', (evt) => {
        if (!allowClose) {
            evt.preventDefault();
            return;
        }
        allowClose = false; // reset supaya modal berikutnya dibuka tetap ter-guard
    });

    modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach((btn) => {
        btn.addEventListener('click', () => { allowClose = true; });
    });
}

// Guard berlaku untuk modal Tambah (modalRth/Tajuk/Potensi) DAN semua modal
// Edit per-baris (editRth123, editTajuk45, dst.) karena keduanya memakai
// peta digitasi Geoman yang rentan memicu auto-close modal Bootstrap.
document.querySelectorAll('.modal').forEach((modalEl) => {
    if (/^(modalRth|modalTajuk|modalPotensi|editRth|editTajuk|editPotensi)/.test(modalEl.id)) {
        setupModalCloseGuard(modalEl.id);
    }
});


/**
 * Inisialisasi peta digitasi dengan toolbar Leaflet-Geoman.
 * Dipakai untuk modal "Tambah" (geometry kosong) MAUPUN modal "Edit"
 * (geometry lama dimuat otomatis sebagai layer yang bisa langsung diedit).
 *
 * options.allowMarker      : true jika mode single-point diperbolehkan (tab Potensi)
 * options.geomFieldId      : id input hidden `geometry` tujuan sinkronisasi (default: 'geom'+capitalize(key))
 * options.previewId        : id elemen teks preview luas (default: 'luasPreview'+capitalize(key))
 * options.previewType      : 'rth' | 'tajuk' | 'potensi' — menentukan format teks preview (default: key)
 * options.initialGeometry  : array [[lat,lng],...] geometry lama (mode edit)
 * options.initialType      : 'polygon' | 'marker' — tipe geometry lama (mode edit)
 */
function initDrawMap(key, elId, options = {}) {
    if (drawState[key]) { drawState[key].map.invalidateSize(); return; }

    const geomFieldId = options.geomFieldId || ('geom' + capitalize(key));
    const previewId   = options.previewId || ('luasPreview' + capitalize(key));
    const previewType = options.previewType || key;

    const map = L.map(elId, { maxZoom: 24 }).setView(CIMAHI, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 24,
        maxNativeZoom: 19 // tile OSM asli mentok di 19, di atas itu di-upscale otomatis
    }).addTo(map);

    // Catatan fix bug modal auto-close:
    // Percobaan sebelumnya memblokir 'keydown'/'keyup' di level container
    // peta ternyata JUSTRU MERUSAK Geoman, karena Geoman sendiri memasang
    // listener Escape/Delete di level `document` untuk membatalkan gambar
    // atau menghapus vertex terakhir — begitu event itu diblokir sebelum
    // sampai ke document, state internal Geoman jadi kacau.
    // Fix modal auto-close yang benar ada di setupModalCloseGuard() di
    // bagian bawah script ini (menahan event 'hide.bs.modal' Bootstrap
    // secara eksplisit, apa pun pemicunya) — bukan dengan memblokir event
    // Leaflet/Geoman.
    map.doubleClickZoom.disable(); // cegah double-click "finish" poligon ikut men-zoom peta
    L.DomEvent.disableClickPropagation(map.getContainer());
    L.DomEvent.disableScrollPropagation(map.getContainer());

    // Toolbar digitasi: hanya tool yang relevan yang ditampilkan
    map.pm.addControls({
        position: 'topleft',
        drawMarker: !!options.allowMarker,
        drawCircleMarker: false,
        drawPolyline: false,
        drawRectangle: true,
        drawPolygon: true,
        drawCircle: false,
        editMode: true,     // geser/edit vertex poligon yang sudah digambar
        dragMode: true,     // geser seluruh bentuk
        removalMode: true,  // hapus bentuk dari peta
        cutPolygon: false,
        rotateMode: false,
    });

    // Panel toggle layer referensi dari GeoServer (Foto Udara, Batas
    // Administrasi, RTH) — membantu user menyesuaikan digitasi dengan
    // kondisi citra/batas wilayah asli, bukan cuma basemap OSM polos.
    addReferenceLayerControl(map, key);


    // Snapping aktif: memudahkan menyambungkan poligon berdekatan (mis. RTH bersebelahan)
    map.pm.setGlobalOptions({ snappable: true, snapDistance: 15 });

    const state = { map, layer: null, geomFieldId, previewId, previewType };
    drawState[key] = state;

    // Saat user selesai menggambar 1 bentuk (poligon/rectangle/marker)
    map.on('pm:create', (e) => {
        // Hanya izinkan 1 geometri aktif per form — hapus yang lama jika ada
        if (state.layer) { map.removeLayer(state.layer); }
        state.layer = e.layer;
        syncGeometryField(key, e.layer);

        // Update field & preview luas saat vertex digeser / bentuk dipindah
        e.layer.on('pm:edit', () => syncGeometryField(key, e.layer));
        e.layer.on('pm:dragend', () => syncGeometryField(key, e.layer));
        e.layer.on('pm:markerdragend', () => syncGeometryField(key, e.layer));
    });

    // Saat bentuk dihapus lewat tool "Remove"
    map.on('pm:remove', (e) => {
        if (e.layer === state.layer) {
            state.layer = null;
            document.getElementById(state.geomFieldId).value = '[]';
            setLuasPreview(key, null);
        }
    });

    // Mode EDIT: muat geometry lama sebagai layer yang langsung bisa digeser/diedit,
    // tanpa perlu menggambar ulang dari nol.
    if (Array.isArray(options.initialGeometry) && options.initialGeometry.length) {
        let layer;
        if (options.initialType === 'marker' || options.initialGeometry.length === 1) {
            const [lat, lng] = options.initialGeometry[0];
            layer = L.marker([lat, lng], { draggable: true });
            layer.addTo(map);
            layer.on('dragend', () => syncGeometryField(key, layer));
            map.setView([lat, lng], 17);
        } else {
            layer = L.polygon(options.initialGeometry, { color: '#0d6efd', weight: 2, fillOpacity: 0.25 });
            layer.addTo(map);
            if (layer.pm) { layer.pm.enable(); } // aktifkan vertex-editing langsung (tanpa klik tool "Edit")
            layer.on('pm:edit', () => syncGeometryField(key, layer));
            layer.on('pm:dragend', () => syncGeometryField(key, layer));
            try { map.fitBounds(layer.getBounds(), { padding: [24, 24], maxZoom: 18 }); } catch (e) { /* abaikan jika bounds tak valid */ }
        }
        state.layer = layer;
        syncGeometryField(key, layer);
    }
}

/**
 * Hitung luas poligon (meter persegi) dari array [lat,lng].
 * Rumus & proyeksi SAMA PERSIS dengan hitungLuasPoligonM2() di
 * Admin/core/DeliniasiModel.php, supaya angka preview di frontend
 * tidak berbeda dengan hasil hitung final di server.
 */
function hitungLuasPoligonM2(points) {
    const n = points.length;
    if (n < 3) return 0;
    const R = 6378137.0; // radius bumi WGS84 (meter)
    const latRef = points[0][0];
    const lngRef = points[0][1];
    const latRefRad = latRef * Math.PI / 180;

    const xy = points.map(([lat, lng]) => {
        const x = ((lng - lngRef) * Math.PI / 180) * R * Math.cos(latRefRad);
        const y = ((lat - latRef) * Math.PI / 180) * R;
        return [x, y];
    });

    let area = 0;
    for (let i = 0; i < n; i++) {
        const [x1, y1] = xy[i];
        const [x2, y2] = xy[(i + 1) % n];
        area += (x1 * y2) - (x2 * y1);
    }
    return Math.abs(area) / 2;
}

/** Sinkronkan koordinat layer Geoman ke input hidden `geometry`, format tetap [[lat,lng],...] sesuai DeliniasiModel.php */
function syncGeometryField(key, layer) {
    const state = drawState[key];
    if (!state) return;

    let points = [];
    let areaM2 = null;

    if (layer instanceof L.Marker) {
        // Titik tunggal (khusus tab Potensi Penanaman)
        const ll = layer.getLatLng();
        points = [[ll.lat, ll.lng]];
    } else if (layer.getLatLngs) {
        // Poligon / rectangle — ambil ring terluar
        let ring = layer.getLatLngs()[0];
        if (Array.isArray(ring[0])) ring = ring[0]; // handle MultiPolygon/nested ring
        points = ring.map(p => [p.lat, p.lng]);
        if (points.length >= 3) {
            areaM2 = hitungLuasPoligonM2(points);
        }
    }

    document.getElementById(state.geomFieldId).value = JSON.stringify(points);
    setLuasPreview(key, areaM2, points.length);
}

function setLuasPreview(key, areaM2, jumlahTitik) {
    const state = drawState[key];
    if (!state) return;
    const el = document.getElementById(state.previewId);
    if (!el) return;

    if (state.previewType === 'rth') {
        el.textContent = areaM2 ? (areaM2 / 10000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' ha' : '- ha';
    } else if (state.previewType === 'tajuk') {
        el.textContent = areaM2 ? areaM2.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' m2' : '- m2';
    } else if (state.previewType === 'potensi') {
        if (areaM2) {
            el.textContent = 'Area potensi: ' + (areaM2 / 10000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' ha';
        } else if (jumlahTitik === 1) {
            el.textContent = 'Lokasi titik tunggal dipilih';
        } else {
            el.textContent = 'Belum ada geometri digambar';
        }
    }
}

/** Hapus bentuk yang sedang digambar & reset form geometry */
function resetDraw(key) {
    const state = drawState[key];
    if (!state) return;
    if (state.layer) {
        state.map.removeLayer(state.layer);
        state.layer = null;
    }
    document.getElementById(state.geomFieldId).value = '[]';
    setLuasPreview(key, null);
}

function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

document.getElementById('modalRth')?.addEventListener('shown.bs.modal', () => initDrawMap('rth', 'mapRth'));
document.getElementById('modalTajuk')?.addEventListener('shown.bs.modal', () => initDrawMap('tajuk', 'mapTajuk'));
document.getElementById('modalPotensi')?.addEventListener('shown.bs.modal', () => initDrawMap('potensi', 'mapPotensi', { allowMarker: true }));

/**
 * Modal EDIT per-baris (id pola: editRth{id}, editTajuk{id}, editPotensi{id}).
 * Elemen peta, input geometry, & preview luas di dalamnya mengikuti pola id
 * yang sama (mapEdit{sisa}, geomEdit{sisa}, luasPreviewEdit{sisa}) — lihat markup
 * PHP modal Edit di atas — sehingga bisa diinisialisasi secara generik di sini.
 */
document.querySelectorAll('.modal[id^="editRth"], .modal[id^="editTajuk"], .modal[id^="editPotensi"]').forEach((modalEl) => {
    modalEl.addEventListener('shown.bs.modal', () => {
        const suffix  = modalEl.id.replace(/^edit/, ''); // mis. "Rth12", "Potensi7"
        const mapId   = 'mapEdit' + suffix;
        const mapDiv  = document.getElementById(mapId);
        if (!mapDiv) return;

        let initialGeometry = [];
        try { initialGeometry = JSON.parse(mapDiv.dataset.editGeometry || '[]'); } catch (e) { /* biarkan kosong */ }
        const initialType = mapDiv.dataset.editType || 'polygon';

        let previewType = 'rth';
        if (suffix.indexOf('Tajuk') === 0) previewType = 'tajuk';
        else if (suffix.indexOf('Potensi') === 0) previewType = 'potensi';

        initDrawMap(modalEl.id, mapId, {
            allowMarker: previewType === 'potensi',
            geomFieldId: 'geomEdit' + suffix,
            previewId: 'luasPreviewEdit' + suffix,
            previewType,
            initialGeometry,
            initialType,
        });
    });
});

/**
 * Peta read-only pada modal "Lihat" (satu geometri per baris tabel).
 * Data geometri diselipkan lewat data-view-geometry/type/color pada div peta.
 */
function renderViewOnlyMap(elId, geometry, type, color) {
    if (viewMapCache[elId]) { viewMapCache[elId].invalidateSize(); return; }

    const map = L.map(elId, { maxZoom: 24 }).setView(CIMAHI, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 24,
        maxNativeZoom: 19
    }).addTo(map);
    L.DomEvent.disableClickPropagation(map.getContainer());
    L.DomEvent.disableScrollPropagation(map.getContainer());

    if (Array.isArray(geometry) && geometry.length) {
        if (type === 'marker' || geometry.length === 1) {
            const [lat, lng] = geometry[0];
            L.marker([lat, lng]).addTo(map);
            map.setView([lat, lng], 17);
        } else {
            const layer = L.polygon(geometry, { color: color || '#0d6efd', weight: 2, fillOpacity: 0.25 }).addTo(map);
            try { map.fitBounds(layer.getBounds(), { padding: [24, 24], maxZoom: 18 }); } catch (e) { /* abaikan */ }
        }
    }
    viewMapCache[elId] = map;
}

document.querySelectorAll('.modal[id^="viewRth"], .modal[id^="viewTajuk"], .modal[id^="viewPotensi"]').forEach((modalEl) => {
    modalEl.addEventListener('shown.bs.modal', () => {
        const mapDiv = modalEl.querySelector('.map-view-only');
        if (!mapDiv) return;
        let geometry = [];
        try { geometry = JSON.parse(mapDiv.dataset.viewGeometry || '[]'); } catch (e) { /* biarkan kosong */ }
        renderViewOnlyMap(mapDiv.id, geometry, mapDiv.dataset.viewType, mapDiv.dataset.viewColor);
    });
});

/**
 * Peta read-only pada modal "Lihat Semua di Peta" (seluruh baris tabel tab aktif
 * ditampilkan sekaligus, masing-masing dengan popup info singkat).
 */
function renderViewAllMap(elId, items) {
    if (viewMapCache[elId]) { viewMapCache[elId].invalidateSize(); return; }

    const map = L.map(elId, { maxZoom: 24 }).setView(CIMAHI, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 24,
        maxNativeZoom: 19
    }).addTo(map);
    L.DomEvent.disableClickPropagation(map.getContainer());
    L.DomEvent.disableScrollPropagation(map.getContainer());

    const allLayers = [];
    (items || []).forEach((item) => {
        if (!Array.isArray(item.geometry) || !item.geometry.length) return;
        let layer;
        if (item.type === 'marker' || item.geometry.length === 1) {
            const [lat, lng] = item.geometry[0];
            layer = L.circleMarker([lat, lng], {
                radius: 7, color: item.color || '#0d6efd', fillColor: item.color || '#0d6efd',
                fillOpacity: 0.9, weight: 2
            });
        } else {
            layer = L.polygon(item.geometry, { color: item.color || '#0d6efd', weight: 2, fillOpacity: 0.25 });
        }
        if (item.popup) layer.bindPopup(item.popup);
        layer.addTo(map);
        allLayers.push(layer);
    });

    if (allLayers.length) {
        try { map.fitBounds(L.featureGroup(allLayers).getBounds(), { padding: [24, 24], maxZoom: 17 }); } catch (e) { /* abaikan */ }
    }
    viewMapCache[elId] = map;
}

['modalViewAllRth', 'modalViewAllTajuk', 'modalViewAllPotensi'].forEach((modalId) => {
    document.getElementById(modalId)?.addEventListener('shown.bs.modal', () => {
        const mapDiv = document.querySelector('#' + modalId + ' .map-view-all');
        if (!mapDiv) return;
        let items = [];
        try { items = JSON.parse(mapDiv.dataset.viewallItems || '[]'); } catch (e) { /* biarkan kosong */ }
        renderViewAllMap(mapDiv.id, items);
    });
});
</script>

<?php require_once 'layouts/footer.php'; ?>