<?php
require_once __DIR__ . '/config.php';

$status_action = null;

/* ======================= PROSES INSERT ======================= */
if (isset($_POST['simpan_bibit'])) {
    $nama_pemohon   = trim($_POST['nama_pemohon'] ?? '');
    $nomor_telepon  = trim($_POST['nomor_telepon'] ?? '');
    $jenis_tanaman  = trim($_POST['jenis_tanaman'] ?? '');
    $jumlah_tanaman = (int) ($_POST['jumlah_tanaman'] ?? 0);
    $lokasi_nanam   = trim($_POST['lokasi_nanam'] ?? '');

    if ($nama_pemohon && $jenis_tanaman && $jumlah_tanaman > 0 && $lokasi_nanam) {
        try {
            $stmt = $config->prepare(
                "INSERT INTO permohonan_bibit (nama_pemohon, nomor_telepon, jenis_tanaman, jumlah_tanaman, lokasi_nanam)
                 VALUES (:nama, :telepon, :jenis, :jumlah, :lokasi)"
            );
            $result = $stmt->execute([
                ':nama'    => $nama_pemohon,
                ':telepon' => $nomor_telepon,
                ':jenis'   => $jenis_tanaman,
                ':jumlah'  => $jumlah_tanaman,
                ':lokasi'  => $lokasi_nanam,
            ]);
            $status_action = $result ? 'success' : 'error';
        } catch (PDOException $e) {
            $status_action = 'error';
        }
    } else {
        $status_action = 'incomplete';
    }
}

/* ======================= AMBIL DATA STOK BIBIT UNTUK DROPDOWN ======================= */
$stokData = [];
try {
    $stmtStok = $config->query("SELECT DISTINCT jenis_tanaman FROM stok_bibit WHERE jumlah_tersedia > 0 ORDER BY jenis_tanaman ASC");
    while ($row = $stmtStok->fetch(PDO::FETCH_ASSOC)) {
        $stokData[] = $row['jenis_tanaman'];
    }
} catch (PDOException $e) {
    // Abaikan jika gagal
}
$stokJson = json_encode($stokData);

/* ======================= AMBIL DATA UNTUK LACAK STATUS ======================= */
$keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';
try {
    if ($keyword !== '') {
        $stmt = $config->prepare(
            "SELECT * FROM permohonan_bibit 
             WHERE nama_pemohon LIKE :keyword OR jenis_tanaman LIKE :keyword2
             ORDER BY tanggal_permohonan DESC"
        );
        $stmt->execute([':keyword' => "%$keyword%", ':keyword2' => "%$keyword%"]);
    } else {
        $stmt = $config->prepare("SELECT * FROM permohonan_bibit ORDER BY tanggal_permohonan DESC");
        $stmt->execute();
    }
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data = [];
}
?>
<?php
$pageTitle = 'Permohonan Bibit Tanaman - Si-TANGKAL Kota Cimahi';
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
  margin: 0; padding: 0; display: flex;
  list-style: none; align-items: center;
}
#navbar > ul > li { position: relative; }
#navbar a {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 0 10px 30px; font-size: 15px;
  color: rgba(255, 255, 255, 0.8); font-weight: 500;
  transition: 0.3s; text-decoration: none;
}
#navbar a:hover, #navbar .active, #navbar li:hover > a { color: #fff; }
.mobile-nav-toggle {
  color: #fff; font-size: 28px; cursor: pointer;
  display: none; line-height: 0; transition: 0.5s;
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
.hero-bibit {
  background: linear-gradient(to bottom, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.8)), url('assets/img/cta-bg.jpg') center center;
  background-size: cover;
  padding: 140px 0 80px 0;
  color: #fff;
  text-align: center;
}
.hero-bibit h1 { font-weight: 700; font-size: 2.5rem; margin-bottom: 15px; }
.hero-bibit p { font-size: 1.1rem; opacity: 0.9; max-width: 600px; margin: 0 auto; }

/* ================= PAGE CONTENT ================= */
.main-content { padding: 60px 0; }

/* ================= CARD ================= */
.custom-card {
  background: #fff; border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.04);
  border: 1px solid rgba(0,0,0,0.05);
  padding: 30px; margin-bottom: 30px;
}
.card-title-icon {
  width: 50px; height: 50px;
  background: rgba(16, 185, 129, 0.1); color: #059669;
  border-radius: 12px; display: flex; align-items: center;
  justify-content: center; font-size: 1.5rem; margin-bottom: 20px;
}

/* ================= FORM ================= */
.form-floating > .form-control,
.form-floating > .form-select {
  border-radius: 10px; border: 1px solid #cbd5e1;
}
.form-floating > .form-control:focus,
.form-floating > .form-select:focus {
  border-color: #059669;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}
.form-floating > label { color: #64748b; }
.btn-submit {
  background: #059669; color: #fff; border: none;
  padding: 14px 30px; border-radius: 10px;
  font-weight: 600; transition: all 0.3s ease;
}
.btn-submit:hover {
  background: #10b981; transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25); color: #fff;
}

/* ================= TABLE ================= */
.table-wrapper {
  background: #fff; border-radius: 12px;
  overflow: hidden; border: 1px solid #e2e8f0;
}
.table { margin-bottom: 0; }
.table thead th {
  background-color: #f8fafc; color: #475569;
  font-weight: 600; font-size: 0.8rem;
  text-transform: uppercase; letter-spacing: 0.5px;
  border-bottom: 1px solid #e2e8f0; padding: 16px;
}
.table tbody td {
  padding: 16px; vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}
.table tbody tr:last-child td { border-bottom: none; }
.table tbody tr:hover { background-color: #f8fafc; }

/* Search Box */
.search-box .form-control {
  border-radius: 10px 0 0 10px;
  border: 1px solid #cbd5e1; border-right: none; padding-left: 20px;
}
.search-box .form-control:focus { box-shadow: none; border-color: #059669; }
.search-box .btn-search {
  border-radius: 0 10px 10px 0;
  background: #059669; border-color: #059669; color: #fff;
}
.search-box .btn-search:hover { background: #10b981; }
.search-box .btn-reset {
  border-radius: 10px; margin-left: 10px;
  border-color: #cbd5e1; color: #64748b;
}
.search-box .btn-reset:hover { background: #f1f5f9; color: #334155; }
</style>
HTML;
require_once __DIR__ . '/includes/site-header.php';
?>

<!-- ======= HERO ======= -->
<section class="hero-bibit">
  <div class="container">
    <h1><i class="bi bi-flower1 me-2"></i>Permohonan Bibit Tanaman</h1>
    <p>Ajukan permohonan bibit tanaman untuk penghijauan lingkungan di wilayah Kota Cimahi.</p>
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
            <i class="bi bi-flower1"></i>
          </div>
          <h5 class="fw-bold mb-4" style="color:#0f172a;">Ajukan Permohonan Bibit</h5>

          <form method="POST">
            <div class="form-floating mb-3">
              <input type="text" name="nama_pemohon" class="form-control" id="nama_pemohon" placeholder="Nama Lengkap" required>
              <label for="nama_pemohon">Nama Pemohon / Instansi</label>
            </div>

            <div class="form-floating mb-3">
              <input type="text" name="nomor_telepon" class="form-control" id="nomor_telepon" placeholder="No Telepon" required>
              <label for="nomor_telepon">Nomor Telepon</label>
            </div>

            <div class="form-floating mb-3">
              <select name="jenis_tanaman" class="form-select" id="jenis_tanaman" required>
                <option value="">-- Pilih Jenis Tanaman --</option>
              </select>
              <label for="jenis_tanaman">Jenis Tanaman yang Dimohon</label>
            </div>

            <div class="form-floating mb-3">
              <input type="number" name="jumlah_tanaman" class="form-control" id="jumlah_tanaman" placeholder="Jumlah" min="1" required>
              <label for="jumlah_tanaman">Jumlah Bibit (batang)</label>
            </div>

            <div class="form-floating mb-4">
              <textarea name="lokasi_nanam" class="form-control" id="lokasi_nanam" placeholder="Alamat" style="height: 120px" required></textarea>
              <label for="lokasi_nanam">Lokasi Penanaman</label>
            </div>

            <button type="submit" name="simpan_bibit" class="btn btn-submit w-100">
              <i class="bi bi-send me-2"></i>Kirim Permohonan
            </button>
          </form>
        </div>
      </div>

      <!-- DATA SECTION (Kanan) -->
      <div class="col-lg-8 mb-4">
        <div class="custom-card">
          <h5 class="fw-bold mb-4" style="color:#0f172a;">Lacak Status Permohonan</h5>

          <form method="GET" class="mb-4">
            <div class="d-flex search-box">
              <input type="text" name="cari" class="form-control" placeholder="Cari berdasarkan Nama atau Jenis Tanaman..." value="<?= htmlspecialchars($keyword) ?>">
              <button type="submit" class="btn btn-search px-4">
                <i class="bi bi-search"></i>
              </button>
              <?php if ($keyword): ?>
                <a href="permohonan_bibit.php" class="btn btn-outline-secondary btn-reset px-3">
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
                  <th width="25%">Informasi Pemohon</th>
                  <th width="25%">Tanaman & Lokasi</th>
                  <th width="15%" class="text-center">Status</th>
                  <th width="30%">Keterangan Admin</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($data)): ?>
                  <?php $no = 1; foreach ($data as $row):
                    $status = $row['status_permohonan'] ?? 'Belum';
                    $badgeClass = match ($status) {
                        'Disetujui' => 'bg-success-subtle text-success',
                        'Ditolak'   => 'bg-danger-subtle text-danger',
                        default     => 'bg-warning-subtle text-warning',
                    };
                    $badgeIcon = match ($status) {
                        'Disetujui' => 'bi-check-circle-fill',
                        'Ditolak'   => 'bi-x-circle-fill',
                        default     => 'bi-clock-history',
                    };
                  ?>
                  <tr>
                    <td class="text-center text-muted"><?= $no++; ?></td>
                    <td>
                      <div class="fw-bold text-dark mb-1" style="font-size:0.95rem;"><?= htmlspecialchars($row['nama_pemohon']); ?></div>
                      <div class="text-muted" style="font-size:0.8rem;">
                        <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($row['tanggal_permohonan'] ?? '-'); ?>
                      </div>
                    </td>
                    <td>
                      <div style="font-size:0.9rem; margin-bottom:4px;">
                        <i class="bi bi-flower1 me-1 text-success"></i><?= htmlspecialchars($row['jenis_tanaman']); ?>
                        <span class="badge bg-primary rounded-pill ms-1"><?= (int)$row['jumlah_tanaman'] ?> btg</span>
                      </div>
                      <div class="text-muted" style="font-size:0.75rem; line-height:1.4;">
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['lokasi_nanam']); ?>
                      </div>
                    </td>
                    <td class="text-center">
                      <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-2" style="border:1px solid currentColor; font-weight:600;">
                        <i class="bi <?= $badgeIcon ?> me-1"></i> <?= htmlspecialchars($status) ?>
                      </span>
                    </td>
                    <td>
                      <?php if (!empty($row['keterangan'])): ?>
                        <div class="p-2 rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:0.82rem; line-height:1.5;">
                          <i class="bi bi-chat-left-text me-1 text-primary"></i>
                          <?= htmlspecialchars($row['keterangan']) ?>
                        </div>
                      <?php else: ?>
                        <span class="text-muted" style="font-size:0.82rem;">Belum ada catatan</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <div class="mb-3 opacity-50">
                        <i class="bi bi-inbox fs-1"></i>
                      </div>
                      <h6 class="fw-semibold">Tidak ada data ditemukan</h6>
                      <p class="mb-0 fs-7">Belum ada riwayat permohonan bibit tanaman.</p>
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
const stokData = <?= $stokJson ?? '{}' ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dropdown tanaman ONCE
    const selectTanaman = document.getElementById('jenis_tanaman');
    stokData.forEach(tanaman => {
        const opt = document.createElement('option');
        opt.value = tanaman;
        opt.textContent = tanaman;
        selectTanaman.appendChild(opt);
    });

    // Initialize
    updateForm();
});

// SweetAlert notifications
document.addEventListener('DOMContentLoaded', function() {
  <?php if ($status_action === 'success'): ?>
  Swal.fire({
    icon: 'success',
    title: 'Permohonan Terkirim!',
    text: 'Permohonan bibit tanaman Anda telah berhasil dikirim dan akan segera diproses.',
    confirmButtonColor: '#059669'
  });
  <?php elseif ($status_action === 'error'): ?>
  Swal.fire({
    icon: 'error',
    title: 'Oops!',
    text: 'Terjadi kesalahan saat menyimpan permohonan. Silakan coba lagi.'
  });
  <?php elseif ($status_action === 'incomplete'): ?>
  Swal.fire({
    icon: 'warning',
    title: 'Data Belum Lengkap',
    text: 'Pastikan semua field telah diisi dengan benar.',
    confirmButtonColor: '#fd7e14'
  });
  <?php endif; ?>
});
</script>

</body>
</html>