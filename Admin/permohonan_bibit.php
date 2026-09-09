<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
require_once 'core/NotificationModel.php';
Rbac::requireAccess($config, 'permohonan_bibit', 'view');
$canCreateBibit = Rbac::can($config, 'permohonan_bibit', 'create');
$canEditBibit   = Rbac::can($config, 'permohonan_bibit', 'edit');
$canDeleteBibit = Rbac::can($config, 'permohonan_bibit', 'delete');

// "Tim Pemeliharaan" bibit = role Petugas Penanaman (role_id 6), pemegang
// hak edit atas modul Permohonan Bibit — menerima notifikasi merah sejak
// permohonan Disetujui sampai serah terima bibit benar-benar selesai.
const ROLE_TIM_PEMELIHARAAN_BIBIT = 6;
$notif = new NotificationModel($config);

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;

$alertMsg  = '';
$alertType = '';

// ===== PROSES TANGGAPI (UPDATE STATUS & KETERANGAN) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tanggapi') {
    if (!$canEditBibit) {
        $alertMsg  = 'Peran Anda tidak memiliki izin menanggapi permohonan bibit.';
        $alertType = 'warning';
    } else {
    $id         = (int) ($_POST['id_bibit'] ?? 0);
    $status     = trim($_POST['status_permohonan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    $allowedStatus = ['Belum', 'Disetujui', 'Ditolak'];

    if ($id > 0 && in_array($status, $allowedStatus)) {
        try {
            if ($status === 'Disetujui') {
                $stmt = $config->prepare(
                    "UPDATE permohonan_bibit
                     SET status_permohonan = :status, keterangan = :keterangan, tanggal_disetujui = CURDATE()
                     WHERE id_bibit = :id"
                );
            } else {
                $stmt = $config->prepare(
                    "UPDATE permohonan_bibit SET status_permohonan = :status, keterangan = :keterangan WHERE id_bibit = :id"
                );
            }
            $stmt->execute([
                ':status'     => $status,
                ':keterangan' => $keterangan ?: null,
                ':id'         => $id,
            ]);

            // Permohonan Disetujui -> notifikasi (merah/belum dibaca) untuk
            // Tim Pemeliharaan agar segera menyiapkan serah terima bibit.
            if ($status === 'Disetujui') {
                $rowStmt = $config->prepare("SELECT nama_pemohon FROM permohonan_bibit WHERE id_bibit = :id");
                $rowStmt->execute([':id' => $id]);
                $nama = $rowStmt->fetchColumn() ?: '-';
                $notif->notifyRoleBibit(
                    ROLE_TIM_PEMELIHARAAN_BIBIT,
                    'Permohonan Bibit Disetujui',
                    "Permohonan bibit dari \"{$nama}\" telah disetujui. Silakan proses serah terima bibit.",
                    'permohonan_bibit.php',
                    $id
                );
            }

            $alertMsg  = 'Status permohonan berhasil diperbarui.';
            $alertType = 'success';
        } catch (PDOException $e) {
            $alertMsg  = 'Gagal memperbarui data: ' . $e->getMessage();
            $alertType = 'danger';
        }
    } else {
        $alertMsg  = 'Data tidak valid. Pastikan status yang dipilih benar.';
        $alertType = 'warning';
    }
    }
}

// ===== PROSES SERAH TERIMA BIBIT (foto + tanggal) -> Selesai =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'serah_terima') {
    if (!$canEditBibit) {
        $alertMsg  = 'Peran Anda tidak memiliki izin memproses serah terima bibit.';
        $alertType = 'warning';
    } else {
        $id = (int) ($_POST['id_bibit'] ?? 0);
        $tanggalST = trim($_POST['tanggal_serah_terima'] ?? '') ?: date('Y-m-d');

        $cekStmt = $config->prepare("SELECT status_permohonan FROM permohonan_bibit WHERE id_bibit = :id");
        $cekStmt->execute([':id' => $id]);
        $statusSaatIni = $cekStmt->fetchColumn();

        if ($id <= 0 || $statusSaatIni !== 'Disetujui') {
            $alertMsg  = 'Serah terima hanya bisa diproses untuk permohonan yang sudah Disetujui.';
            $alertType = 'warning';
        } elseif (empty($_FILES['foto_serah_terima']['name']) || $_FILES['foto_serah_terima']['error'] !== UPLOAD_ERR_OK) {
            $alertMsg  = 'Foto serah terima wajib diunggah.';
            $alertType = 'warning';
        } else {
            $folder  = __DIR__ . '/../images/';
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['foto_serah_terima']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $alertMsg  = 'Format foto tidak didukung. Gunakan JPG, PNG, atau GIF.';
                $alertType = 'warning';
            } else {
                $namaFile = 'serahterima_' . $id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto_serah_terima']['tmp_name'], $folder . $namaFile)) {
                    try {
                        $stmt = $config->prepare(
                            "UPDATE permohonan_bibit
                             SET status_permohonan = 'Selesai',
                                 foto_serah_terima = :foto,
                                 tanggal_serah_terima = :tanggal
                             WHERE id_bibit = :id"
                        );
                        $stmt->execute([
                            ':foto'    => $namaFile,
                            ':tanggal' => $tanggalST,
                            ':id'      => $id,
                        ]);
                        // Serah terima selesai -> notifikasi merah Tim Pemeliharaan hilang.
                        $notif->markReadByBibit($id);
                        $alertMsg  = 'Serah terima bibit berhasil dicatat, permohonan selesai.';
                        $alertType = 'success';
                    } catch (PDOException $e) {
                        $alertMsg  = 'Gagal menyimpan serah terima: ' . $e->getMessage();
                        $alertType = 'danger';
                    }
                } else {
                    $alertMsg  = 'Gagal mengunggah foto serah terima.';
                    $alertType = 'danger';
                }
            }
        }
    }
}

// ===== PROSES HAPUS =====
if (isset($_GET['hapus'])) {
    if (!$canDeleteBibit) {
        header('Location: permohonan_bibit.php?deleted=forbidden');
        exit;
    }
    $hapusId = (int) $_GET['hapus'];
    if ($hapusId > 0) {
        try {
            $stmt = $config->prepare("DELETE FROM permohonan_bibit WHERE id_bibit = :id");
            $stmt->execute([':id' => $hapusId]);
            header("Location: permohonan_bibit.php?deleted=1");
            exit;
        } catch (PDOException $e) {
            header("Location: permohonan_bibit.php?deleted=0");
            exit;
        }
    }
}

// Alert dari redirect hapus
if (isset($_GET['deleted'])) {
    if ($_GET['deleted'] === 'forbidden') {
        $alertMsg  = 'Peran Anda tidak memiliki izin menghapus permohonan bibit.';
        $alertType = 'warning';
    } else {
        $alertMsg  = $_GET['deleted'] == '1' ? 'Data permohonan bibit berhasil dihapus.' : 'Gagal menghapus data.';
        $alertType = $_GET['deleted'] == '1' ? 'success' : 'danger';
    }
}

// ===== FILTER: tanggal, status, jenis tanaman (dropdown dari entri yang ada) =====
$filterTanggal = trim($_GET['tanggal'] ?? '');
$filterStatus  = trim($_GET['status'] ?? '');
$filterJenis   = trim($_GET['jenis'] ?? '');

// ===== AMBIL SEMUA DATA (dengan filter opsional) =====
try {
    $sql    = "SELECT * FROM permohonan_bibit WHERE 1=1";
    $params = [];
    if ($filterTanggal !== '') {
        $sql .= " AND tanggal_permohonan = :tanggal";
        $params[':tanggal'] = $filterTanggal;
    }
    if ($filterStatus !== '') {
        $sql .= " AND status_permohonan = :status";
        $params[':status'] = $filterStatus;
    }
    if ($filterJenis !== '') {
        $sql .= " AND jenis_tanaman = :jenis";
        $params[':jenis'] = $filterJenis;
    }
    $sql .= " ORDER BY tanggal_permohonan DESC, id_bibit DESC";

    $stmt = $config->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Data untuk dropdown filter (mirip filter Excel: hanya nilai yang ada)
    $daftarTanggal = $config->query("SELECT DISTINCT tanggal_permohonan FROM permohonan_bibit WHERE tanggal_permohonan IS NOT NULL ORDER BY tanggal_permohonan DESC")->fetchAll(PDO::FETCH_COLUMN);
    $daftarJenis   = $config->query("SELECT DISTINCT jenis_tanaman FROM permohonan_bibit WHERE jenis_tanaman IS NOT NULL AND jenis_tanaman != '' ORDER BY jenis_tanaman ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $data = [];
    $daftarTanggal = [];
    $daftarJenis   = [];
    $alertMsg  = 'Gagal mengambil data: ' . $e->getMessage();
    $alertType = 'danger';
}

$pageTitle  = 'Permohonan Bibit Tanaman';
$activePage = 'permohonan_bibit';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--text-primary);">Permohonan Bibit Tanaman</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Kelola semua permohonan bibit tanaman dari masyarakat</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
            $exportModul        = 'permohonan_bibit';
            $exportLabel        = 'Permohonan Bibit';
            $exportSupportsDate = true;
            require 'layouts/export_modal.php';
        ?>
        <span class="badge rounded-pill" style="background:var(--accent-light); color:#0d7a3e; font-size:0.75rem; padding:0.45em 0.9em;">
            <i class="bi bi-flower1 me-1"></i> <?= count($data) ?> Data
        </span>
    </div>
</div>

<!-- Alert -->
<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert" style="border-radius:var(--radius-md); border:none;">
    <i class="bi bi-<?= $alertType === 'success' ? 'check-circle' : ($alertType === 'danger' ? 'x-circle' : 'exclamation-triangle') ?> me-2"></i>
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>



<!-- ======= FILTER BAR (tanggal, status, jenis tanaman) ======= -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="tanggal" class="form-select form-select-sm" style="min-width:150px;">
                <option value="">-- Semua Tanggal --</option>
                <?php foreach ($daftarTanggal as $tgl): ?>
                <option value="<?= htmlspecialchars($tgl) ?>" <?= $filterTanggal === $tgl ? 'selected' : '' ?>><?= htmlspecialchars($tgl) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select form-select-sm" style="min-width:140px;">
                <option value="">-- Semua Status --</option>
                <?php foreach (['Belum', 'Disetujui', 'Ditolak', 'Selesai'] as $st): ?>
                <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
            <select name="jenis" class="form-select form-select-sm" style="min-width:160px;">
                <option value="">-- Semua Jenis Tanaman --</option>
                <?php foreach ($daftarJenis as $jn): ?>
                <option value="<?= htmlspecialchars($jn) ?>" <?= $filterJenis === $jn ? 'selected' : '' ?>><?= htmlspecialchars($jn) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-search"></i> Terapkan</button>
            <?php if ($filterTanggal || $filterStatus || $filterJenis): ?>
            <a href="permohonan_bibit.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ======= DATA TABLE ======= -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span>
            <i class="bi bi-table me-2"></i>Daftar Permohonan Bibit
            <span class="badge bg-secondary ms-1"><?= count($data) ?></span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No</th>
                        <th>Nama Pemohon</th>
                        <th>No Telepon</th>
                        <th>Jenis Tanaman</th>
                        <th class="text-center">Jumlah</th>
                        <th>Lokasi Tanam</th>
                        <th>Tgl Permohonan</th>
                        <th class="text-center">Status</th>
                        <th>Serah Terima</th>
                        <th>Keterangan</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                            <td class="fw-500"><?= htmlspecialchars($row['nama_pemohon']) ?></td>
                            <td><?= htmlspecialchars($row['nomor_telepon'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['jenis_tanaman']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary rounded-pill"><?= (int)$row['jumlah_tanaman'] ?></span>
                            </td>
                            <td>
                                <span style="max-width:180px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                      title="<?= htmlspecialchars($row['lokasi_nanam']) ?>">
                                    <?= htmlspecialchars($row['lokasi_nanam']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted" style="font-size:0.8rem;">
                                    <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($row['tanggal_permohonan'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php
                                    $status = $row['status_permohonan'] ?? 'Belum';
                                    $badgeClass = match ($status) {
                                        'Disetujui' => 'bg-success',
                                        'Ditolak'   => 'bg-danger',
                                        'Selesai'   => 'bg-primary',
                                        default     => 'bg-warning text-dark',
                                    };
                                ?>
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td style="font-size:0.78rem;">
                                <?php if (!empty($row['foto_serah_terima'])): ?>
                                    <a href="../images/<?= htmlspecialchars($row['foto_serah_terima']) ?>" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-image me-1 text-primary"></i>
                                        <?= htmlspecialchars($row['tanggal_serah_terima'] ?? '-') ?>
                                    </a>
                                <?php elseif ($status === 'Disetujui'): ?>
                                    <span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Menunggu serah terima</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="max-width:160px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.8rem;"
                                      title="<?= htmlspecialchars($row['keterangan'] ?? '-') ?>">
                                    <?= htmlspecialchars($row['keterangan'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="text-center pe-3" style="white-space:nowrap;">
                                <?php if ($canEditBibit): ?>
                                <!-- Tombol Tanggapi -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="Tanggapi Permohonan"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalTanggapi"
                                        data-id="<?= (int)$row['id_bibit'] ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_pemohon']) ?>"
                                        data-telepon="<?= htmlspecialchars($row['nomor_telepon'] ?? '-') ?>"
                                        data-jenis="<?= htmlspecialchars($row['jenis_tanaman']) ?>"
                                        data-jumlah="<?= (int)$row['jumlah_tanaman'] ?>"
                                        data-status="<?= htmlspecialchars($status) ?>"
                                        data-keterangan="<?= htmlspecialchars($row['keterangan'] ?? '') ?>">
                                    <i class="bi bi-chat-left-text"></i>
                                </button>
                                <?php if ($status === 'Disetujui'): ?>
                                <!-- Tombol Serah Terima -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-success me-1"
                                        title="Catat Serah Terima Bibit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalSerahTerima"
                                        data-id="<?= (int)$row['id_bibit'] ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_pemohon']) ?>">
                                    <i class="bi bi-box-seam"></i>
                                </button>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($canDeleteBibit): ?>
                                <!-- Tombol Hapus -->
                                <a href="permohonan_bibit.php?hapus=<?= (int)$row['id_bibit'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus permohonan dari <?= htmlspecialchars(addslashes($row['nama_pemohon'])) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Belum ada data permohonan bibit tanaman
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======= MODAL: TANGGAPI PERMOHONAN ======= -->
<div class="modal fade" id="modalTanggapi" tabindex="-1" aria-labelledby="modalTanggapiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius-lg); border:none; box-shadow:var(--shadow-lg);">
            <form method="POST">
                <input type="hidden" name="action" value="tanggapi">
                <input type="hidden" name="id_bibit" id="modal_id_bibit">

                <div class="modal-header" style="border-bottom:1px solid var(--border-color);">
                    <h5 class="modal-title fw-bold" id="modalTanggapiLabel">
                        <i class="bi bi-chat-left-text me-2 text-primary"></i>Tanggapi Permohonan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body py-3">
                    <!-- Info Pemohon (Read-only) -->
                    <div class="rounded-3 p-3 mb-3" style="background:#f8fafc; border:1px solid var(--border-color);">
                        <div class="row g-2">
                            <div class="col-12">
                                <small class="text-muted d-block" style="font-size:0.7rem;">NAMA PEMOHON</small>
                                <span class="fw-600" id="modal_nama" style="font-size:0.9rem;">-</span>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block" style="font-size:0.7rem;">NO TELEPON</small>
                                <span class="fw-600" id="modal_telepon" style="font-size:0.9rem;">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block" style="font-size:0.7rem;">JENIS TANAMAN</small>
                                <span id="modal_jenis" style="font-size:0.85rem;">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block" style="font-size:0.7rem;">JUMLAH</small>
                                <span id="modal_jumlah" style="font-size:0.85rem;">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="mb-3">
                        <label class="form-label" for="modal_status">
                            Status Permohonan <span class="text-danger">*</span>
                        </label>
                        <select name="status_permohonan" id="modal_status" class="form-select" required>
                            <option value="Belum">Belum</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Ditolak">Ditolak</option>
                        </select>
                    </div>

                    <!-- Keterangan Textarea -->
                    <div class="mb-2">
                        <label class="form-label" for="modal_keterangan">
                            Keterangan / Catatan untuk Pemohon
                        </label>
                        <textarea name="keterangan" id="modal_keterangan" class="form-control" rows="3"
                                  placeholder="Tulis pesan atau catatan untuk pemohon..."></textarea>
                        <div class="form-text" style="font-size:0.75rem;">Opsional. Pesan ini akan ditampilkan kepada pemohon.</div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Simpan Tanggapan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======= MODAL: SERAH TERIMA BIBIT ======= -->
<div class="modal fade" id="modalSerahTerima" tabindex="-1" aria-labelledby="modalSerahTerimaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius-lg); border:none; box-shadow:var(--shadow-lg);">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="serah_terima">
                <input type="hidden" name="id_bibit" id="st_id_bibit">

                <div class="modal-header" style="border-bottom:1px solid var(--border-color);">
                    <h5 class="modal-title fw-bold" id="modalSerahTerimaLabel">
                        <i class="bi bi-box-seam me-2 text-success"></i>Serah Terima Bibit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body py-3">
                    <p class="text-muted" style="font-size:0.85rem;">
                        Untuk: <strong id="st_nama">-</strong>. Notifikasi Tim Pemeliharaan akan hilang setelah data ini disimpan.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="st_tanggal">Tanggal Serah Terima <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_serah_terima" id="st_tanggal" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="st_foto">Foto Serah Terima <span class="text-danger">*</span></label>
                        <input type="file" name="foto_serah_terima" id="st_foto" class="form-control"
                               accept=".jpg,.jpeg,.png,.gif" required>
                        <div class="form-text" style="font-size:0.75rem;">Format: JPG, PNG, GIF.</div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Simpan Serah Terima
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script: Populate modal data from button attributes -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalTanggapi = document.getElementById('modalTanggapi');
    if (modalTanggapi) {
        modalTanggapi.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            document.getElementById('modal_id_bibit').value       = button.getAttribute('data-id');
            document.getElementById('modal_nama').textContent     = button.getAttribute('data-nama');
            document.getElementById('modal_telepon').textContent  = button.getAttribute('data-telepon');
            document.getElementById('modal_jenis').textContent    = button.getAttribute('data-jenis');
            document.getElementById('modal_jumlah').textContent   = button.getAttribute('data-jumlah') + ' batang';
            document.getElementById('modal_status').value         = button.getAttribute('data-status');
            document.getElementById('modal_keterangan').value     = button.getAttribute('data-keterangan');
        });
    }

    const modalSerahTerima = document.getElementById('modalSerahTerima');
    if (modalSerahTerima) {
        modalSerahTerima.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('st_id_bibit').value = button.getAttribute('data-id');
            document.getElementById('st_nama').textContent = button.getAttribute('data-nama');
        });
    }
});
</script>

<?php require_once 'layouts/footer.php'; ?>