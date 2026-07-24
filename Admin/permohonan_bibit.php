<?php
@ob_start();

// ===== KONFIGURASI TERPADU (session, DB, class Auth) =====
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');

$alertMsg  = '';
$alertType = '';

// ===== PROSES TANGGAPI (UPDATE STATUS & KETERANGAN) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tanggapi') {
    $id         = (int) ($_POST['id_bibit'] ?? 0);
    $status     = trim($_POST['status_permohonan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    $allowedStatus = ['Belum', 'Disetujui', 'Ditolak'];

    if ($id > 0 && in_array($status, $allowedStatus)) {
        try {
            $stmt = $config->prepare("UPDATE permohonan_bibit SET status_permohonan = :status, keterangan = :keterangan WHERE id_bibit = :id");
            $stmt->execute([
                ':status'     => $status,
                ':keterangan' => $keterangan ?: null,
                ':id'         => $id,
            ]);
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

// ===== PROSES HAPUS =====
if (isset($_GET['hapus'])) {
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
    $alertMsg  = $_GET['deleted'] == '1' ? 'Data permohonan bibit berhasil dihapus.' : 'Gagal menghapus data.';
    $alertType = $_GET['deleted'] == '1' ? 'success' : 'danger';
}

// ===== AMBIL SEMUA DATA =====
try {
    $stmt = $config->prepare("SELECT * FROM permohonan_bibit ORDER BY tanggal_permohonan DESC, id_bibit DESC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data = [];
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
    <span class="badge rounded-pill" style="background:var(--accent-light); color:#0d7a3e; font-size:0.75rem; padding:0.45em 0.9em;">
        <i class="bi bi-flower1 me-1"></i> <?= count($data) ?> Data
    </span>
</div>

<!-- Alert -->
<?php if ($alertMsg): ?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert" style="border-radius:var(--radius-md); border:none;">
    <i class="bi bi-<?= $alertType === 'success' ? 'check-circle' : ($alertType === 'danger' ? 'x-circle' : 'exclamation-triangle') ?> me-2"></i>
    <?= htmlspecialchars($alertMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>



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
                                        default     => 'bg-warning text-dark',
                                    };
                                ?>
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td>
                                <span style="max-width:160px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.8rem;"
                                      title="<?= htmlspecialchars($row['keterangan'] ?? '-') ?>">
                                    <?= htmlspecialchars($row['keterangan'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="text-center pe-3" style="white-space:nowrap;">
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
                                <!-- Tombol Hapus -->
                                <a href="permohonan_bibit.php?hapus=<?= (int)$row['id_bibit'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus permohonan dari <?= htmlspecialchars(addslashes($row['nama_pemohon'])) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
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
});
</script>

<?php require_once 'layouts/footer.php'; ?>