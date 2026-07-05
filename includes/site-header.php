<?php
/**
 * includes/site-header.php
 * ---------------------------------------------------------
 * Header/navbar YANG SAMA untuk semua halaman publik gaya
 * "landing" (index.php, Pengajuan.php, permohonan_bibit.php,
 * detail.php, dst). Dipisah dari isi halaman supaya setiap
 * halaman cukup melakukan:
 *
 *     $activeNav = 'home'; // 'home' | 'peta' | 'pengajuan' | 'kontak'
 *     require_once __DIR__ . '/includes/site-header.php';
 *
 * Halaman TETAP boleh punya <title>/<style> sendiri sebelum
 * memanggil file ini — cukup set $pageTitle & $extraHead.
 *
 * Login SEKARANG SELALU di /login.php (di luar folder /login/).
 * ---------------------------------------------------------
 */
$activeNav = $activeNav ?? 'home';
$pageTitle = $pageTitle ?? 'Si-TANGKAL - Kota Cimahi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta content="Sistem Informasi Terpadu Lingkungan dan Alam Kota Cimahi" name="description">

  <link href="assets/img/cimahi.ico" rel="icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <!-- CSS umum untuk seluruh halaman publik -->
  <link href="assets/css/variables.css" rel="stylesheet">
  <link href="assets/css/base.css" rel="stylesheet">

  <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
  <?php endforeach; endif; ?>
  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body>

  <!-- ======= HEADER ======= -->
  <header id="header" class="fixed-top">
    <div class="container d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo">
        <img src="assets/img/logo.png" alt="Si-TANGKAL" class="img-fluid">
      </a>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto <?= $activeNav === 'home' ? 'active' : '' ?>" href="index.php">Beranda</a></li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle scrollto <?= $activeNav === 'peta' ? 'active' : '' ?>" href="#" id="navbarDropdownPeta" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Peta
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownPeta">
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="maps.php">
                  <i class="bi bi-map" style="font-size:16px;"></i>
                  Peta 2D
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="map-3d.php">
                  <i class="bi bi-box" style="font-size:16px;"></i>
                  Peta 3D
                </a>
              </li>
            </ul>
          </li>

          <li><a class="nav-link scrollto" href="https://dlh.cimahikota.go.id" target="_blank">Link Terkait</a></li>
          <li><a class="nav-link scrollto <?= $activeNav === 'kontak' ? 'active' : '' ?>" href="index.php#contact">Kontak</a></li>

          <!-- Dropdown Pengajuan -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle scrollto <?= $activeNav === 'pengajuan' ? 'active' : '' ?>" href="#" id="navbarDropdownPengajuan" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Pengajuan
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownPengajuan">
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="Pengajuan.php">
                  <i class="bi bi-scissors" style="font-size:16px;"></i>
                  Pemangkasan Pohon
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="permohonan_bibit.php">
                  <i class="bi bi-flower1" style="font-size:16px;"></i>
                  Permohonan Bibit Tanaman
                </a>
              </li>
            </ul>
          </li>

          <?php if (class_exists('Auth') && Auth::isLoggedIn()): ?>
            <li><a class="btn btn-success rounded-pill px-4 ms-lg-3 text-white" style="font-weight: 600;" href="Admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
          <?php else: ?>
            <li><a class="btn btn-success rounded-pill px-4 ms-lg-3 text-white" style="font-weight: 600;" href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a></li>
          <?php endif; ?>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>
