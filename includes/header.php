<?php
/**
 * Public site header / floating navbar.
 * Expects optional $activeNav variable ('home'|'about'|'dashboard') to highlight nav item.
 */
$activeNav = $activeNav ?? '';

/**
 * Mode "embed": dipakai saat halaman ini dimuat lewat <iframe> di dalam
 * dashboard Admin (Admin/map.php) supaya elemen Home / Notifikasi / Avatar
 * & Logout pada navbar tidak bentrok dengan header & sidebar Admin.
 * Aktifkan lewat query string ?embed=1 pada src iframe, atau dengan
 * men-set $embedMode = true sebelum file ini di-include.
 */
$embedMode = $embedMode ?? (isset($_GET['embed']) && $_GET['embed'] == '1');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Si-TANGKAL' : 'Si-TANGKAL — Sistem Informasi Pohon Kota Cimahi' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="assets/css/variables.css">
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/navbar.css">
<link rel="stylesheet" href="assets/css/panels.css">
<link rel="stylesheet" href="assets/css/map.css">
<link rel="stylesheet" href="assets/css/components.css">
<link rel="stylesheet" href="assets/css/responsive.css">
<?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
<?php endforeach; endif; ?>
<?php if (!empty($extraHead)): echo $extraHead; endif; ?>
</head>
<body>
<nav class="navbar glass">
    <div class="navbar-left">
        <a href="index.php" class="logo">
            <span class="logo-icon">🌳</span>
            <span class="logo-text">
                <span class="logo-title">Si-TANGKAL</span>
                <span class="logo-subtitle">Sistem Informasi Pohon Kota Cimahi</span>
            </span>
        </a>
    </div>
    <div class="navbar-center">
        <form action="index.php" method="get" class="search-box" id="navbar-search-form">
            <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="q" id="navbar-search-input" placeholder="Cari nama pohon, kesehatan, atau famili..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off">
        </form>
        <script>
        // Jika berada di halaman peta (runTreeSearch tersedia dari map.js),
        // pencarian dijalankan langsung tanpa reload halaman.
        (function () {
            const form = document.getElementById('navbar-search-form');
            const input = document.getElementById('navbar-search-input');
            if (!form || !input) return;

            let debounceTimer = null;

            function liveSearch() {
                if (typeof runTreeSearch === 'function') {
                    runTreeSearch(input.value.trim());
                }
            }

            form.addEventListener('submit', function (e) {
                if (typeof runTreeSearch === 'function') {
                    e.preventDefault();
                    liveSearch();
                }
                // Jika tidak di halaman peta, biarkan form submit normal (reload ke index.php?q=...)
            });

            input.addEventListener('input', function () {
                if (typeof runTreeSearch !== 'function') return;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(liveSearch, 300);
            });
        })();
        </script>
    </div>
    <div class="navbar-right">
        <?php if (!$embedMode): ?>
         <a href="index.php" class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="m3 9 9-7 9 7"></path>
    <path d="M9 22V12h6v10"></path>
    <path d="M21 22H3"></path>
    <path d="M21 9v13"></path>
    <path d="M3 9v13"></path>
</svg>
            Home
        </a>
        <?php endif; ?>
        <a href="map-3d.php" class="nav-link <?= $activeNav === 'map3d' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Peta 3D
        </a>
       
        <?php if (class_exists('Auth') && Auth::isLoggedIn()): $sitangkalUser = Auth::user(); ?>
            <?php if (!$embedMode): ?>
            <div class="navbar-user">
                <button class="navbar-avatar-btn" id="navbar-avatar-btn" onclick="toggleNavbarUserMenu(event)">
                    <span class="navbar-avatar"><?= strtoupper(substr($sitangkalUser['name'] ?? 'A', 0, 1)) ?></span>
                </button>
                <div class="navbar-user-dropdown" id="navbar-user-dropdown">
                    <div class="navbar-user-dropdown-header">
                        <div class="navbar-user-dropdown-name"><?= htmlspecialchars($sitangkalUser['name'] ?? 'Admin') ?></div>
                        <div class="navbar-user-dropdown-role"><?= htmlspecialchars($sitangkalUser['department'] ?? 'Si-TANGKAL') ?></div>
                    </div>
                    <a href="Admin/index.php" class="navbar-user-dropdown-item">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                        Dashboard
                    </a>
                    <a href="Admin/profile.php" class="navbar-user-dropdown-item">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M4 21v-1a8 8 0 0116 0v1"></path></svg>
                        Profil Saya
                    </a>
                    <!-- <a href="#" class="navbar-user-dropdown-item">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"></path><path d="M22 6l-10 7L2 6"></path></svg>
                        Pesan
                        <span class="navbar-badge-inline">3</span>
                    </a>
                    <a href="#" class="navbar-user-dropdown-item">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"></path></svg>
                        Pengaturan
                    </a> -->
                    <a href="logout.php" class="navbar-user-dropdown-item danger">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        Keluar
                    </a>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <a href="login.php" class="btn-login">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                Login
            </a>
        <?php endif; ?>
    </div>
</nav>

<?php if (class_exists('Auth') && Auth::isLoggedIn()): ?>
<script>
    function toggleNavbarUserMenu(e) {
        e.stopPropagation();
        document.getElementById('navbar-user-dropdown').classList.toggle('open');
    }
    document.addEventListener('click', function (e) {
        const dropdown = document.getElementById('navbar-user-dropdown');
        const btn = document.getElementById('navbar-avatar-btn');
        if (dropdown && dropdown.classList.contains('open') && !dropdown.contains(e.target) && e.target !== btn) {
            dropdown.classList.remove('open');
        }
    });
</script>
<?php endif; ?>