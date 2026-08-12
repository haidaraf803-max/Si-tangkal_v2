<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');

// ===== PROSES CRUD =====

// 1. Tambah Data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stok'])) {
    $jenis_tanaman = trim($_POST['jenis_tanaman'] ?? '');
    $jumlah = (int)($_POST['jumlah_tersedia'] ?? 0);
    $sumber_array = $_POST['sumber_bibit'] ?? [];
    if (!is_array($sumber_array)) {
        $sumber_array = [$sumber_array];
    }

    if ($jenis_tanaman && $jumlah >= 0 && count($sumber_array) > 0) {
        try {
            $stmt = $config->prepare("INSERT INTO stok_bibit (jenis_tanaman, jumlah_tersedia, sumber_bibit) VALUES (:jenis, :jumlah, :sumber)");
            foreach ($sumber_array as $sumber) {
                if ($sumber === 'Mandiri') $sumber = 'Pembibitan Mandiri';
                $stmt->execute([':jenis' => $jenis_tanaman, ':jumlah' => $jumlah, ':sumber' => $sumber]);
            }
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data stok berhasil ditambahkan!'];
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal menambah data: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Data tidak valid. Periksa inputan Anda.'];
    }
    header("Location: stok_bibit.php");
    exit;
}

// 2. Edit Data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_stok'])) {
    $id_stok = (int)($_POST['id_stok'] ?? 0);
    $jenis_tanaman = trim($_POST['jenis_tanaman'] ?? '');
    $jumlah = (int)($_POST['jumlah_tersedia'] ?? 0);
    $sumber_array = $_POST['sumber_bibit'] ?? [];
    if (!is_array($sumber_array)) {
        $sumber_array = [$sumber_array];
    }

    if ($id_stok > 0 && $jenis_tanaman && $jumlah >= 0 && count($sumber_array) > 0) {
        try {
            $first_sumber = array_shift($sumber_array);
            if ($first_sumber === 'Mandiri') $first_sumber = 'Pembibitan Mandiri';
            
            $stmtUpdate = $config->prepare("UPDATE stok_bibit SET jenis_tanaman = :jenis, jumlah_tersedia = :jumlah, sumber_bibit = :sumber WHERE id_stok = :id");
            $stmtUpdate->execute([':jenis' => $jenis_tanaman, ':jumlah' => $jumlah, ':sumber' => $first_sumber, ':id' => $id_stok]);
            
            if (count($sumber_array) > 0) {
                $stmtInsert = $config->prepare("INSERT INTO stok_bibit (jenis_tanaman, jumlah_tersedia, sumber_bibit) VALUES (:jenis, :jumlah, :sumber)");
                foreach ($sumber_array as $sumber) {
                    if ($sumber === 'Mandiri') $sumber = 'Pembibitan Mandiri';
                    $stmtInsert->execute([':jenis' => $jenis_tanaman, ':jumlah' => $jumlah, ':sumber' => $sumber]);
                }
            }
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data stok berhasil diperbarui!'];
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal memperbarui data: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Data tidak valid. Periksa inputan Anda.'];
    }
    header("Location: stok_bibit.php");
    exit;
}

// 3. Hapus Data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_stok'])) {
    $id_stok = (int)($_POST['id_stok'] ?? 0);
    if ($id_stok > 0) {
        try {
            $stmt = $config->prepare("DELETE FROM stok_bibit WHERE id_stok = :id");
            $stmt->execute([':id' => $id_stok]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data stok berhasil dihapus!'];
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal menghapus data: ' . $e->getMessage()];
        }
    }
    header("Location: stok_bibit.php");
    exit;
}

// ===== AMBIL DATA =====
$filter_sumber = $_GET['filter_sumber'] ?? '';

try {
    $query = "SELECT * FROM stok_bibit";
    $params = [];
    if (!empty($filter_sumber) && $filter_sumber !== 'Semua Kategori') {
        $query .= " WHERE sumber_bibit = :sumber";
        $params[':sumber'] = $filter_sumber;
    }
    $query .= " ORDER BY sumber_bibit ASC, jenis_tanaman ASC";
    
    $stmt = $config->prepare($query);
    $stmt->execute($params);
    $stokData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}

$pageTitle  = 'Kelola Stok Bibit';
$activePage = 'stok_bibit';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= MAIN CONTENT ======= -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--text-primary);">Kelola Stok Bibit</h4>
        <p class="text-muted mb-0" style="font-size:0.85rem;">Manajemen persediaan bibit tanaman (APBD & Mandiri)</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-success" href="export_stok_bibit.php<?= (!empty($filter_sumber) && $filter_sumber !== 'Semua Kategori') ? '?filter_sumber=' . urlencode($filter_sumber) : '' ?>" target="_blank">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahStok">
            <i class="bi bi-plus-lg me-1"></i> Tambah Stok Baru
        </button>
    </div>
</div>

<!-- Pesan Flash -->
<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Filter Kategori Pendanaan -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: var(--radius-md);">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label text-muted small fw-bold">Filter Kategori Pendanaan</label>
                <select name="filter_sumber" class="form-select">
                    <option value="Semua Kategori" <?= ($filter_sumber === 'Semua Kategori' || $filter_sumber === '') ? 'selected' : '' ?>>Semua Kategori</option>
                    <option value="APBD" <?= ($filter_sumber === 'APBD') ? 'selected' : '' ?>>APBD</option>
                    <option value="Pembibitan Mandiri" <?= ($filter_sumber === 'Pembibitan Mandiri' || $filter_sumber === 'Mandiri') ? 'selected' : '' ?>>Mandiri</option>
                    <option value="Hibah" <?= ($filter_sumber === 'Hibah') ? 'selected' : '' ?>>Hibah</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-success me-2"><i class="bi bi-funnel me-1"></i> Filter</button>
                <?php if(!empty($filter_sumber) && $filter_sumber !== 'Semua Kategori'): ?>
                    <a href="stok_bibit.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- TABEL DATA -->
<div class="card border-0 shadow-sm" style="border-radius: var(--radius-md);">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 text-muted text-uppercase" style="font-size:0.8rem; width:5%;">No</th>
                        <th class="text-muted text-uppercase" style="font-size:0.8rem; width:25%;">Jenis Tanaman</th>
                        <th class="text-muted text-uppercase" style="font-size:0.8rem; width:20%;">Sumber Bibit</th>
                        <th class="text-center text-muted text-uppercase" style="font-size:0.8rem; width:15%;">Jumlah Stok</th>
                        <th class="text-center text-muted text-uppercase" style="font-size:0.8rem; width:15%;">Tanggal Update</th>
                        <th class="text-center text-muted text-uppercase pe-4" style="font-size:0.8rem; width:15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stokData)): ?>
                        <?php $no = 1; foreach ($stokData as $row): 
                            $stok = (int)$row['jumlah_tersedia'];
                            $sumber = htmlspecialchars($row['sumber_bibit']);
                            $sumberClass = ($sumber === 'APBD') ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success';
                            $isTerbatas = ($stok < 10) ? 'text-danger fw-bold' : '';
                            $tgl_update = !empty($row['tanggal_update']) ? date('d-m-Y', strtotime($row['tanggal_update'])) : '-';
                        ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= $no++ ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['jenis_tanaman']) ?></td>
                            <td>
                                <span class="badge rounded-pill <?= $sumberClass ?> px-3"><?= $sumber ?></span>
                            </td>
                            <td class="text-center fs-5 <?= $isTerbatas ?>">
                                <?= $stok ?> <span class="fs-7 text-muted fw-normal">btg</span>
                            </td>
                            <td class="text-center text-muted">
                                <?= $tgl_update ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-2">
                                    <!-- Tombol Edit -->
                                    <button class="btn btn-sm btn-outline-warning" title="Edit Data" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditStok<?= $row['id_stok'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Form Hapus -->
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus bibit <?= htmlspecialchars($row['jenis_tanaman']) ?>?');">
                                        <input type="hidden" name="id_stok" value="<?= $row['id_stok'] ?>">
                                        <button type="submit" name="delete_stok" class="btn btn-sm btn-outline-danger" title="Hapus Data">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Modal Edit Stok -->
                                <div class="modal fade text-start" id="modalEditStok<?= $row['id_stok'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form method="POST" class="modal-content border-0 shadow">
                                            <div class="modal-header border-bottom-0 pb-0">
                                                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Stok Bibit</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_stok" value="<?= $row['id_stok'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Jenis Tanaman</label>
                                                    <input type="text" class="form-control" name="jenis_tanaman" value="<?= htmlspecialchars($row['jenis_tanaman']) ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Sumber Bibit</label>
                                                    <div class="d-flex flex-column gap-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="sumber_bibit[]" id="edit_kat_apbd<?= $row['id_stok'] ?>" value="APBD" <?= ($sumber === 'APBD') ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="edit_kat_apbd<?= $row['id_stok'] ?>">APBD</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="sumber_bibit[]" id="edit_kat_mandiri<?= $row['id_stok'] ?>" value="Pembibitan Mandiri" <?= ($sumber === 'Mandiri' || $sumber === 'Pembibitan Mandiri') ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="edit_kat_mandiri<?= $row['id_stok'] ?>">Mandiri</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="sumber_bibit[]" id="edit_kat_hibah<?= $row['id_stok'] ?>" value="Hibah" <?= ($sumber === 'Hibah') ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="edit_kat_hibah<?= $row['id_stok'] ?>">Hibah</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Jumlah Tersedia</label>
                                                    <input type="number" class="form-control" name="jumlah_tersedia" value="<?= $stok ?>" min="0" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top-0 pt-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_stok" class="btn btn-warning px-4 text-white">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <!-- End Modal Edit -->

                            </td>
                        </tr>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-50"></i>
                                <h6 class="fw-bold">Belum Ada Data Stok Bibit</h6>
                                <p class="mb-0 fs-7">Silakan klik "Tambah Stok Baru" untuk memasukkan data pertama.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Stok -->
<div class="modal fade" id="modalTambahStok" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Stok Bibit Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-dark fw-bold">Kategori Pendanaan (Sumber Bibit)</label>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sumber_bibit[]" id="stok_kat_apbd" value="APBD" checked>
                            <label class="form-check-label" for="stok_kat_apbd">APBD</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sumber_bibit[]" id="stok_kat_mandiri" value="Pembibitan Mandiri">
                            <label class="form-check-label" for="stok_kat_mandiri">Mandiri</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sumber_bibit[]" id="stok_kat_hibah" value="Hibah">
                            <label class="form-check-label" for="stok_kat_hibah">Hibah</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Jenis Tanaman</label>
                    <input type="text" class="form-control" name="jenis_tanaman" placeholder="Contoh: Pohon Mahoni" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Jumlah Tersedia (Awal)</label>
                    <input type="number" class="form-control" name="jumlah_tersedia" value="0" min="0" required>
                </div>

            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add_stok" class="btn btn-primary px-4">Simpan Data</button>
            </div>
        </form>
    </div>
</div>



<?php require_once 'layouts/footer.php'; ?>
