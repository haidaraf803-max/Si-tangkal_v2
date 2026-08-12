<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Admin/core/PengajuanModel.php';

$pengajuan = new PengajuanModel($config);
$status_action = null;

/* ======================= PROSES INSERT ======================= */
if (isset($_POST['simpan'])) {
    // Input pemangkasan/penebangan pohon WAJIB login terlebih dahulu.
    if (!Auth::isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode('Pengajuan.php'));
        exit;
    }

    $namaFileBaru = '';
    $status_action = null;

    // Proses upload jika ada file
    if (isset($_FILES['foto_pohon']) && $_FILES['foto_pohon']['error'] !== UPLOAD_ERR_NO_FILE) {
        $folder   = __DIR__ . '/images/';
        $allowed  = ['jpg', 'jpeg', 'png', 'gif'];
        $maxSize  = 5 * 1024 * 1024; // 5 MB

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $file = $_FILES['foto_pohon'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $status_action = 'error_simpan';
        } elseif (!in_array($ext, $allowed)) {
            $status_action = 'error_format';
        } elseif ($file['size'] > $maxSize) {
            $status_action = 'error_size';
        } else {
            $newName = uniqid('dok_', true) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $folder . $newName)) {
                $namaFileBaru = $newName;
            } else {
                $status_action = 'error_simpan';
            }
        }
    }

    if ($status_action === null) {
        $result = $pengajuan->create(
            $_POST['no_surat'],
            $_POST['nama_pemohon'],
            $_POST['nomor_telepon'],
            $_POST['lokasi_pohon'],
            $namaFileBaru
        );
        $status_action = $result ? 'success_simpan' : 'error_simpan';
    }
}

/* ======================= SEARCH ======================= */
$keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';
if ($keyword != '') {
    $data = $pengajuan->search($keyword);
} else {
    $data = $pengajuan->getAll();
}
?>
<?php
$pageTitle = 'Pengajuan Pemangkasan Pohon - Si-TANGKAL Kota Cimahi';
$activeNav = 'pengajuan';
$extraHead = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
  font-family: 'Inter', sans-serif;
  background-color: #f8f9fa;
  color: #334155;
}

/* ================= HEADER ================= */
#header {
  background: rgba(15, 23, 42, 0.95);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(10px);
  padding: 15px 0;
  transition: all 0.3s;
}
#header .logo img {
  max-height: 40px;
  object-fit: contain;
}
#navbar > ul {
  margin: 0;
  padding: 0;
  display: flex;
  list-style: none;
  align-items: center;
}
#navbar > ul > li {
  position: relative;
}
#navbar a {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0 10px 30px;
  font-size: 15px;
  color: rgba(255, 255, 255, 0.8);
  font-weight: 500;
  transition: 0.3s;
  text-decoration: none;
}
#navbar a:hover, #navbar .active, #navbar li:hover > a {
  color: #fff;
}
.mobile-nav-toggle {
  color: #fff;
  font-size: 28px;
  cursor: pointer;
  display: none;
  line-height: 0;
  transition: 0.5s;
}
@media (max-width: 991px) {
  .mobile-nav-toggle { display: block; }
  #navbar > ul { display: none; }
}

/* Dropdown styling matching Dashboard (Light Mode) */
.navbar .dropdown-menu {
  display: none;
  position: absolute;
  background: #ffffff; /* Putih bersih seperti dashboard */
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: 10px 0;
  min-width: 250px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  margin-top: 8px;
  transform: translateY(10px);
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.navbar .dropdown-menu.show {
  display: block;
  transform: translateY(0);
}
.header-scrolled .navbar .dropdown-menu {
  background: #ffffff; 
}
.navbar .dropdown-item {
  color: #334155 !important; /* Teks gelap */
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  border-radius: 8px;
  margin: 0 8px;
  width: auto;
}
.navbar .dropdown-item:hover,
.navbar .dropdown-item:focus {
  background: #f8fafc !important; /* Hover abu-abu terang */
  color: #059669 !important; /* Teks berubah hijau sitangkal */
}
.navbar .dropdown-item:active {
  background: #e2e8f0 !important;
}
.navbar .dropdown-divider {
  border-color: #f1f5f9;
  margin: 4px 16px;
}

/* ================= HERO SECTION ================= */
.hero-pengajuan {
  background: linear-gradient(to bottom, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.8)), url('assets/img/cta-bg.jpg') center center;
  background-size: cover;
  padding: 140px 0 80px 0;
  color: #fff;
  text-align: center;
  position: relative;
}
.hero-pengajuan h1 {
  font-weight: 700;
  font-size: 2.5rem;
  margin-bottom: 15px;
}
.hero-pengajuan p {
  font-size: 1.1rem;
  opacity: 0.9;
  max-width: 600px;
  margin: 0 auto;
}

/* ================= PAGE CONTENT ================= */
.main-content {
  padding: 60px 0;
}

/* ================= CARD ================= */
.custom-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.04);
  border: 1px solid rgba(0,0,0,0.05);
  padding: 30px;
  margin-bottom: 30px;
}
.card-title-icon {
  width: 50px;
  height: 50px;
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 20px;
}

/* ================= FORM ================= */
.form-floating > .form-control {
  border-radius: 10px;
  border: 1px solid #cbd5e1;
}
.form-floating > .form-control:focus {
  border-color: #059669;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}
.form-floating > label {
  color: #64748b;
}
.btn-submit {
  background: #059669;
  color: #fff;
  border: none;
  padding: 14px 30px;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.3s ease;
}
.btn-submit:hover {
  background: #10b981;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
  color: #fff;
}

/* ================= TABLE ================= */
.table-wrapper {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}
.table {
  margin-bottom: 0;
}
.table thead th {
  background-color: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #e2e8f0;
  padding: 16px;
}
.table tbody td {
  padding: 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}
.table tbody tr:last-child td {
  border-bottom: none;
}
.table tbody tr:hover {
  background-color: #f8fafc;
}

/* Search Box */
.search-box .form-control {
  border-radius: 10px 0 0 10px;
  border: 1px solid #cbd5e1;
  border-right: none;
  padding-left: 20px;
}
.search-box .form-control:focus {
  box-shadow: none;
  border-color: #059669;
}
.search-box .btn-search {
  border-radius: 0 10px 10px 0;
  background: #059669;
  border-color: #059669;
  color: #fff;
}
.search-box .btn-search:hover {
  background: #10b981;
}
.search-box .btn-reset {
  border-radius: 10px;
  margin-left: 10px;
  border-color: #cbd5e1;
  color: #64748b;
}
.search-box .btn-reset:hover {
  background: #f1f5f9;
  color: #334155;
}
</style>
HTML;
require_once __DIR__ . '/includes/site-header.php';
?>
<!-- ======= HERO ======= -->
<section class="hero-pengajuan">
  <div class="container">
    <h1>Layanan Pengajuan</h1>
    <p>Formulir permohonan pemangkasan atau penebangan pohon yang berpotensi membahayakan di wilayah Kota Cimahi.</p>
  </div>
</section>

<!-- ======= MAIN CONTENT ======= -->
<div class="main-content">
  <div class="container">

    <div class="row align-items-start">
      
      <!-- FORM SECTION (Kiri) -->
      <div class="col-lg-4 mb-4">
        <div class="custom-card sticky-top" style="top: 100px;">
          <div class="card-title-icon">
            <i class="bi bi-file-earmark-plus"></i>
          </div>
          <h5 class="fw-bold mb-4" style="color:#0f172a;">Buat Pengajuan Baru</h5>

          <?php if (Auth::isLoggedIn()): ?>
          <form method="POST" enctype="multipart/form-data">
            <div class="form-floating mb-3">
              <input type="text" name="no_surat" class="form-control" id="no_surat" placeholder="001/DLH/2026" required>
              <label for="no_surat">No. Surat Permohonan</label>
            </div>

            <div class="form-floating mb-3">
              <input type="text" name="nama_pemohon" class="form-control" id="nama_pemohon" placeholder="Nama Lengkap / Instansi" required>
              <label for="nama_pemohon">Nama Pemohon / Instansi</label>
            </div>

            <div class="form-floating mb-3">
              <input type="text" name="nomor_telepon" class="form-control" id="nomor_telepon" placeholder="No Telepon" required>
              <label for="nomor_telepon">Nomber Telepon</label>
            </div>

            <div class="form-floating mb-3">
              <textarea name="lokasi_pohon" class="form-control" id="lokasi_pohon" placeholder="Alamat detail" style="height: 120px" required></textarea>
              <label for="lokasi_pohon">Lokasi Detail Pohon</label>
            </div>

            <div class="mb-4">
              <label for="foto_pohon" class="form-label text-muted" style="font-size:0.9rem;">Foto Keadaan Pohon (Opsional)</label>
              <input class="form-control" type="file" id="foto_pohon" name="foto_pohon" accept=".jpg,.jpeg,.png,.gif">
              <div class="form-text" style="font-size:0.8rem;">Format: JPG, PNG, GIF. Maks: 5 MB</div>
            </div>

            <button type="submit" name="simpan" class="btn btn-submit w-100">
              <i class="bi bi-send me-2"></i>Kirim Pengajuan
            </button>
          </form>
          <?php else: ?>
          <div class="text-center py-3">
            <i class="bi bi-lock text-muted mb-3 d-block" style="font-size: 2.5rem;"></i>
            <p class="text-muted mb-4" style="font-size:0.9rem;">
              Anda harus <strong>login</strong> terlebih dahulu untuk dapat mengajukan permohonan pemangkasan atau penebangan pohon.
            </p>
            <a href="login.php?redirect=<?= urlencode('Pengajuan.php') ?>" class="btn btn-submit w-100">
              <i class="bi bi-box-arrow-in-right me-2"></i>Login untuk Mengajukan
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- DATA SECTION (Kanan) -->
      <div class="col-lg-8 mb-4">
        <div class="custom-card">
          <h5 class="fw-bold mb-4" style="color:#0f172a;">Lacak Status Pengajuan</h5>
          
          <form method="GET" class="mb-4">
            <div class="d-flex search-box">
              <input type="text" name="cari" class="form-control" placeholder="Cari berdasarkan No Surat atau Nama Pemohon..." value="<?= htmlspecialchars($keyword) ?>">
              <button type="submit" class="btn btn-search px-4">
                <i class="bi bi-search"></i>
              </button>
              <?php if ($keyword): ?>
                <a href="Pengajuan.php" class="btn btn-outline-secondary btn-reset px-3">
                  <i class="bi bi-x-lg"></i>
                </a>
              <?php endif; ?>
            </div>
          </form>

          <div class="table-wrapper table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th width="5%" class="text-center">No</th>
                  <th width="35%">Informasi Pemohon</th>
                  <th width="40%">Lokasi & Jadwal</th>
                  <th width="20%" class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($data)): ?>
                  <?php $no = 1; foreach ($data as $row): 
                    $ket = strtolower(trim($row['Keterangan'] ?? ''));
                    $isDone = ($ket === 'sudah');
                  ?>
                  <tr>
                    <td class="text-center text-muted"><?= $no++; ?></td>
                    <td>
                      <div class="fw-bold text-dark mb-1" style="font-size:0.95rem;"><?= htmlspecialchars($row['Nama_Pemohon']); ?></div>
                      <div class="text-muted" style="font-size:0.8rem;">
                        <i class="bi bi-file-text me-1"></i><?= htmlspecialchars($row['No_Surat']); ?>
                      </div>
                    </td>
                    <td>
                      <div style="font-size:0.9rem; margin-bottom:4px; line-height:1.4;">
                        <?= htmlspecialchars($row['Lokasi_Pohon']); ?>
                      </div>
                      <div class="text-muted" style="font-size:0.75rem;">
                        <i class="bi bi-calendar-event me-1"></i> Penanganan: 
                        <span class="fw-medium">
                          <?= !empty($row['Tanggal_Penanganan']) ? htmlspecialchars($row['Tanggal_Penanganan']) : 'Belum dijadwalkan'; ?>
                        </span>
                      </div>
                    </td>
                    <td class="text-center">
                      <?php if ($isDone): ?>
                        <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2" style="border:1px solid #bbf7d0; font-weight:600;">
                          <i class="bi bi-check-circle-fill me-1"></i> Sudah
                        </span>
                      <?php else: ?>
                        <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2" style="border:1px solid #fecaca; font-weight:600;">
                          <i class="bi bi-clock-history me-1"></i> Belum
                        </span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                      <div class="mb-3 opacity-50">
                        <i class="bi bi-inbox fs-1"></i>
                      </div>
                      <h6 class="fw-semibold">Tidak ada data ditemukan</h6>
                      <p class="mb-0 fs-7">Belum ada riwayat pengajuan pemangkasan pohon.</p>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/site-footer.php'; ?>
<script>
// Alerts
document.addEventListener('DOMContentLoaded', function() {
  <?php if ($status_action === 'success_simpan'): ?>
  Swal.fire({
    icon: 'success',
    title: 'Pengajuan Terkirim!',
    text: 'Permohonan Anda akan segera diproses oleh petugas kami.',
    confirmButtonColor: '#059669'
  });
  <?php elseif ($status_action === 'error_simpan'): ?>
  Swal.fire({
    icon: 'error',
    title: 'Oops!',
    text: 'Terjadi kesalahan saat menyimpan data pengajuan Anda. Silakan coba lagi.'
  });
  <?php elseif ($status_action === 'error_format'): ?>
  Swal.fire({
    icon: 'warning',
    title: 'Format Tidak Didukung',
    text: 'Hanya file JPG, PNG, dan GIF yang diizinkan untuk foto pohon.',
    confirmButtonColor: '#fd7e14'
  });
  <?php elseif ($status_action === 'error_size'): ?>
  Swal.fire({
    icon: 'warning',
    title: 'File Terlalu Besar',
    text: 'Ukuran foto maksimal adalah 5 MB.',
    confirmButtonColor: '#fd7e14'
  });
  <?php endif; ?>
});
</script>
</body>
</html>