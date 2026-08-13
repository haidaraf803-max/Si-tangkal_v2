<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Admin/core/PohonModel.php';

$pohonModel = new PohonModel($config);
$totalSehat = $pohonModel->countByKondisi('Sehat');
$totalKurangBaik = $pohonModel->countByKondisi('Kurang Sehat');
$totalMati = $pohonModel->countByKondisi('Sakit');

?>
<?php
$pageTitle = 'Si-TANGKAL - Kota Cimahi';
$activeNav = 'home';
$extraHead = <<<'HTML'
<style>
    :root {
      --primary: #059669;
      --primary-light: #10b981;
      --primary-dark: #047857;
      --secondary: #e2e8f0;
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --bg-light: #f8fafc;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      color: var(--text-dark);
      overflow-x: hidden;
      background-color: #fff;
    }

    h1, h2, h3, h4, h5, h6, .navbar a, .btn-get-started {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* ================= HEADER ================= */
    #header {
      background: transparent;
      transition: all 0.4s ease-in-out;
      padding: 24px 0;
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 997;
    }
    #header.header-scrolled {
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(12px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      padding: 16px 0;
    }
    #header .logo {
      display: flex;
      align-items: center;
    }
    #header .logo img {
      max-height: 48px;
      object-fit: contain;
      image-rendering: -webkit-optimize-contrast;
      image-rendering: crisp-edges;
      transition: all 0.3s;
    }
    .navbar > ul {
      margin: 0; padding: 0; display: flex; list-style: none; align-items: center; gap: 8px;
    }
    .navbar > ul > li { position: relative; }
    .navbar a {
      display: flex; align-items: center; justify-content: space-between;
      padding: 8px 16px; font-size: 15px; font-weight: 600;
      color: rgba(255, 255, 255, 0.85); transition: 0.3s; text-decoration: none;
      letter-spacing: 0.3px; border-radius: 8px;
    }
    .navbar a:hover, .navbar .active, .navbar li:hover > a {
      color: #fff;
      background: rgba(255, 255, 255, 0.1);
    }
    .mobile-nav-toggle {
      color: #fff; font-size: 28px; cursor: pointer; display: none; line-height: 0; transition: 0.5s;
    }
    @media (max-width: 991px) {
      .mobile-nav-toggle { display: block; }
      .navbar > ul { display: none; }
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
    #hero {
      width: 100%;
      height: 100vh;
      background: url('assets/img/cta-bg.jpg') center center no-repeat;
      background-size: cover;
      position: relative;
      background-attachment: fixed;
    }
    #hero::before {
      content: '';
      background: linear-gradient(to bottom, rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.4));
      position: absolute;
      bottom: 0; top: 0; left: 0; right: 0;
    }
    #hero .container {
      position: relative;
      z-index: 1;
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }
    #hero h3 {
      color: var(--primary-light);
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 16px;
      letter-spacing: 3px;
      text-transform: uppercase;
    }
    #hero h3 strong {
      font-weight: 800;
    }
    #hero h1 {
      margin: 0 0 20px 0;
      font-size: 56px;
      font-weight: 800;
      line-height: 1.15;
      color: #fff;
      max-width: 900px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    #hero h2 {
      color: rgba(255, 255, 255, 0.9);
      margin-bottom: 48px;
      font-size: 22px;
      font-weight: 400;
      max-width: 700px;
      font-family: 'Inter', sans-serif;
    }
    .btn-get-started {
      font-weight: 700;
      font-size: 16px;
      letter-spacing: 0.5px;
      display: inline-block;
      padding: 16px 48px;
      border-radius: 50px;
      transition: all 0.3s ease;
      color: #fff;
      background: var(--primary);
      text-decoration: none;
      box-shadow: 0 4px 15px rgba(5, 150, 105, 0.4);
    }
    .btn-get-started:hover {
      background: var(--primary-light);
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
      color: #fff;
    }

    /* ================= SECTION TITLES ================= */
    .section-title {
      text-align: center;
      padding-bottom: 60px;
    }
    .section-title h2 {
      font-size: 14px;
      letter-spacing: 2.5px;
      font-weight: 700;
      padding: 8px 24px;
      margin: 0;
      background: rgba(16, 185, 129, 0.1);
      color: var(--primary);
      display: inline-block;
      text-transform: uppercase;
      border-radius: 50px;
    }
    .section-title h3 {
      margin: 20px 0 0 0;
      font-size: 36px;
      font-weight: 800;
      color: var(--text-dark);
    }
    .section-title h3 span {
      color: var(--primary);
    }

    /* ================= STATS (Kondisi Pohon) ================= */
    .stats-section {
      padding: 100px 0;
      background: var(--bg-light);
    }
    .stat-card {
      background: #fff;
      border-radius: 24px;
      padding: 40px 30px;
      text-align: center;
      box-shadow: 0 4px 20px rgba(0,0,0,0.03);
      transition: all 0.4s ease;
      border: 1px solid rgba(226, 232, 240, 0.8);
      height: 100%;
    }
    .stat-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.08);
      border-color: transparent;
    }
    .stat-icon {
      width: 80px; height: 80px;
      border-radius: 20px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 24px auto;
      font-size: 36px;
      transition: all 0.3s ease;
    }
    .stat-card:hover .stat-icon {
      transform: scale(1.1) rotate(5deg);
    }
    .stat-sehat .stat-icon { background: #dcfce7; color: #16a34a; }
    .stat-sedang .stat-icon { background: #fef08a; color: #ca8a04; }
    .stat-mati .stat-icon { background: #fee2e2; color: #dc2626; }
    .stat-card h3 { font-size: 56px; font-weight: 800; margin-bottom: 8px; color: var(--text-dark); line-height: 1; }
    .stat-card p { color: var(--text-muted); font-weight: 600; font-size: 15px; margin: 0; text-transform: uppercase; letter-spacing: 1.5px; }

    /* ================= ABOUT / PROLOG ================= */
    .about {
      padding: 120px 0;
    }
    .about-text {
      font-size: 17px;
      line-height: 1.8;
      color: var(--text-muted);
      margin-bottom: 24px;
    }

    /* ================= SERVICES / FUNGSI RTH ================= */
    .services {
      padding: 100px 0;
      background: var(--bg-light);
    }
    .service-box {
      padding: 48px 40px;
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.03);
      height: 100%;
      transition: all 0.4s ease;
      border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .service-box:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.08);
      border-color: transparent;
    }
   .service-icon {
    margin: 0 auto 30px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.service-icon img {
    width: 72px;
    height: 72px;
    object-fit: contain;
    transition: all 0.3s ease;
}
    .service-box h4 {
      font-weight: 700;
      font-size: 22px;
      margin-bottom: 16px;
      color: var(--text-dark);
    }
    .service-box p {
      font-size: 16px;
      line-height: 1.7;
      color: var(--text-muted);
    }

    /* ================= CTA ================= */
    .cta {
      background: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.85)), url('assets/img/cta-bg.jpg') center center;
      background-size: cover;
      background-attachment: fixed;
      padding: 100px 0;
      text-align: center;
    }
    .cta h3 { color: #fff; font-size: 36px; font-weight: 800; margin-bottom: 20px; }
    .cta p { color: rgba(255,255,255,0.85); font-size: 18px; max-width: 800px; margin: 0 auto; line-height: 1.8; font-family: 'Inter', sans-serif; }

    /* ================= CONTACT ================= */
    .contact { padding: 120px 0; }
    .info-box {
      background: #fff;
      padding: 32px;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.03);
      margin-bottom: 24px;
      border: 1px solid rgba(226, 232, 240, 0.8);
      transition: all 0.3s ease;
    }
    .info-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.06);
    }
    .info-box i {
      font-size: 36px;
      color: var(--primary);
      margin-bottom: 16px;
      display: inline-block;
      background: rgba(16, 185, 129, 0.1);
      width: 72px;
      height: 72px;
      line-height: 72px;
      border-radius: 50%;
    }
    .info-box h4 { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: var(--text-dark); }
    .info-box p { font-size: 15px; color: var(--text-muted); margin: 0; line-height: 1.6; }
    
    .contact-form {
      background: #fff;
      padding: 8px;
      border-radius: 24px;
      box-shadow: 0 4px 30px rgba(0,0,0,0.04);
      border: 1px solid rgba(226, 232, 240, 0.8);
      height: 100%;
    }
    
    /* ================= FOOTER ================= */
    #footer {
      background: #0f172a;
      color: #fff;
      padding: 80px 0 30px 0;
      font-size: 15px;
    }
    #footer h4 {
      font-size: 18px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 24px;
      letter-spacing: 0.5px;
    }
    #footer p { color: #94a3b8; line-height: 1.8; }
    #footer .footer-links ul { list-style: none; padding: 0; margin: 0; }
    #footer .footer-links ul li { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    #footer .footer-links ul li:last-child { border-bottom: none; }
    #footer .footer-links ul a { color: #94a3b8; text-decoration: none; transition: 0.3s; font-weight: 500; }
    #footer .footer-links ul a:hover { color: var(--primary-light); padding-left: 8px; }
    .copyright { text-align: center; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 60px; color: #94a3b8; font-size: 14px; }
    
    /* Responsive Fixes */
    @media (max-width: 768px) {
      #hero h1 { font-size: 40px; }
      #hero h2 { font-size: 18px; }
      .stat-card h3 { font-size: 48px; }
      .section-title h3 { font-size: 30px; }
      .about, .services, .stats-section, .cta, .contact { padding: 80px 0; }
    }
  </style>
HTML;
require_once __DIR__ . '/includes/site-header.php';
?>
  <!-- ======= HERO ======= -->
<section id="hero">
  <div class="container text-center">
    <h3><strong>Si-TANGKAL</strong></h3>
    <h1>Sistem Informasi Terpadu Lingkungan dan Alam</h1>
    <h2>Kota Cimahi</h2>

    <div class="dropdown mt-3">
      <button class="btn-get-started dropdown-toggle btn-get-started" 
              type="button" 
              data-bs-toggle="dropdown" 
              aria-expanded="false">
        Jelajahi Peta
      </button>

      <ul class="dropdown-menu">
        <li>
          <a class="dropdown-item" href="maps.php" target="_blank">
            Peta 2D
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="map-3d.php" target="_blank">
            Peta 3D
          </a>
        </li>
      </ul>
    </div>

  </div>
</section>

  <!-- ======= MAIN ======= -->
  <main id="main">

    <!-- ======= STATS KONDISI POHON ======= -->
    <section class="stats-section">
      <div class="container">
        <div class="section-title">
          <h2>Data Monitoring</h2>
          <h3>Rekapitulasi <span>Kondisi Pohon</span></h3>
        </div>

        <div class="row g-4 justify-content-center">
          <div class="col-lg-4 col-md-6">
            <div class="stat-card stat-sehat">
              <div class="stat-icon"><i class="bi bi-tree-fill"></i></div>
              <h3><?= $totalSehat ?></h3>
              <p>Pohon Sehat</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="stat-card stat-sedang">
              <div class="stat-icon"><i class="bi bi-tree"></i></div>
              <h3><?= $totalKurangBaik ?></h3>
              <p>Kurang Baik</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="stat-card stat-mati">
              <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
              <h3><?= $totalMati ?></h3>
              <p>Pohon Mati</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ======= ABOUT / PROLOG ======= -->
    <section id="about" class="about bg-white">
      <div class="container">
        <div class="section-title">
          <h2>Prolog</h2>
          <h3>Mengenal <span>Si-TANGKAL</span></h3>
        </div>

        <div class="row gy-4">
          <div class="col-lg-6">
            <p class="about-text" style="text-align: justify;">
              Cimahi merupakan wilayah yang baru ditetapkan sebagai kota pada tahun 2001. Sejak awal pembentukannya, Kota Cimahi telah menunjukkan perkembangan dan kemajuan yang cukup pesat sehingga perlu diikuti oleh upaya-upaya menjaga keseimbangan antara lingkungan sosial dengan ekonomi. Salah satunya adalah komposisi ruang terbuka hijau (RTH).
            </p>
            <p class="about-text" style="text-align: justify;">
              Undang-Undang No. 26 Tahun 2007 tentang Penataan Ruang mengamanatkan adanya alokasi untuk Ruang Terbuka Hijau minimal 30% dari wilayah kota, dengan komposisi 20% RTH publik dan 10% RTH privat.
            </p>
          </div>
          <div class="col-lg-6">
            <div class="p-4 rounded-4" style="background:#f8fafc; border:1px solid #e2e8f0;">
              <h5 class="fw-bold mb-3" style="color:var(--primary);"><i class="bi bi-info-circle me-2"></i>WebGIS RTH Kota Cimahi</h5>
              <p class="about-text mb-0" style="text-align: justify; font-size:15px;">
                Aplikasi ini dapat diakses secara luas oleh masyarakat dan menyediakan kemudahan dalam mengakses informasi dan data pohon penyusun RTH. Selain itu, portal ini juga menyediakan layanan pengaduan masyarakat terkait pohon (seperti pohon tumbang atau rusak) sehingga dapat ditangani secara cepat dan tepat oleh petugas kami.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ======= FUNGSI RTH ======= -->
    <section id="services" class="services">
      <div class="container">
        <div class="section-title">
          <h2>Fungsi</h2>
          <h3>Pentingnya <span>RTH</span></h3>
        </div>

        <div class="row g-4">
          <div class="col-md-4">
            <div class="service-box text-center">
              <div class="service-icon"><img src="assets/img/ecology.png" alt="Ekologi"></div>
              <h4>Fungsi Ekologi</h4>
              <p class="text-muted">RTH merupakan ‘paru-paru’ kota atau wilayah yang menyerap karbondioksida dan memproduksi oksigen.</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="service-box text-center">
              <div class="service-icon"><img src="assets/img/tree.png" alt="Estetis"></div>
              <h4>Fungsi Estetis</h4>
              <p class="text-muted">RTH dapat memperindah suatu wilayah, memberikan pemandangan hijau yang menyejukkan mata.</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="service-box text-center">
              <div class="service-icon"><img src="assets/img/leaf.png" alt="Planologi"></div>
              <h4>Fungsi Planologi</h4>
              <p class="text-muted">RTH menjadi pembatas (buffer) antara satu ruang dengan ruang lainnya demi tata letak yang baik.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ======= CTA ======= -->
    <section class="cta">
      <div class="container">
        <h3>Mari Jaga Ruang Terbuka Hijau Bersama</h3>
        <p>Green Open Space (RTH) adalah area terbuka yang menjadi tempat tumbuh tanaman, memberikan banyak manfaat bagi kualitas udara dan kehidupan masyarakat kota Cimahi.</p>
      </div>
    </section>

    <!-- ======= CONTACT ======= -->
    <section id="contact" class="contact bg-white">
      <div class="container">
        <div class="section-title">
          <h2>Kontak Kami</h2>
          <h3>Hubungi <span>DLH Kota Cimahi</span></h3>
        </div>

        <div class="row g-4 mt-2">
          <div class="col-lg-4">
            <div class="info-box text-center">
              <i class="bi bi-geo-alt"></i>
              <h4>Lokasi</h4>
              <p>Dinas Lingkungan Hidup Gd. C Lt. 4<br>Komplek Perkantoran Pemkot Cimahi</p>
            </div>
            <div class="info-box text-center">
              <i class="bi bi-envelope"></i>
              <h4>Email</h4>
              <p>dlh@cimahikota.go.id</p>
            </div>
            <div class="info-box text-center mb-0">
              <i class="bi bi-telephone"></i>
              <h4>Telepon</h4>
              <p>(022) 6632614</p>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="contact-form h-100">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2355.3199865345396!2d107.55344677835517!3d-6.8713917844878925!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e43e5be9efcb%3A0x33a14603de233c9d!2sEnvironmental%20Agency%20of%20Cimahi!5e0!3m2!1sen!2sid!4v1663768807534!5m2!1sen!2sid" width="100%" height="100%" style="border:0; border-radius: 12px; min-height: 350px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <?php require_once __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>