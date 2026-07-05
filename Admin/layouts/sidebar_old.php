<?php
/**
 * sidebar.php
 * Sidebar navigasi admin yang modern dan responsif.
 * Variabel yang dibutuhkan:
 *   - $activePage : string — nama halaman aktif ('dashboard','pengajuan','pohon','profile')
 *   - $_SESSION['admin']['Name'] : nama admin dari sesi
 */

$adminName  = htmlspecialchars($_SESSION['admin']['Name'] ?? 'Admin');
$adminType  = htmlspecialchars($_SESSION['admin']['Type'] ?? 'Administrator');
$activePage = $activePage ?? '';

// Daftar menu utama
$menuItems = [
    [
        'href'  => 'index.php',
        'icon'  => 'bi-speedometer2',
        'label' => 'Dashboard',
        'key'   => 'dashboard',
    ],
    [
        'href'  => 'pengajuan.php',
        'icon'  => 'bi-file-earmark-text',
        'label' => 'Pengajuan Pemangkasan Pohon',
        'key'   => 'pengajuan',
    ],
    [
        'href'  => 'permohonan_bibit.php',
        'icon'  => 'bi-flower1',
        'label' => 'Permohonan Bibit Tanaman',
        'key'   => 'permohonan_bibit',
    ],
    [
        'href'  => 'pohon.php',
        'icon'  => 'bi-tree',
        'label' => 'Kondisi Pohon',
        'key'   => 'pohon',
    ],
    [
        'href'  => 'stok_bibit.php',
        'icon'  => 'bi-box-seam',
        'label' => 'Stok Bibit',
        'key'   => 'stok_bibit',
    ],
    // [
    //     'href'  => 'stok_bibit.php',
    //     'icon'  => 'bi bi-display',
    //     'label' => 'Monitoring',
    //     'key'   => 'monitoring',
    // ],
    //    [
    //     'href'  => 'stok_bibit.php',
    //     'icon'  => 'bi bi-people',
    //     'label' => 'User Management',
    //     'key'   => 'user-management',
    // ],
    
    [
        'href'  => 'map.php',
        'icon'  => 'bi bi-map',
        'label' => 'Peta',
        'key'   => 'map',
    ]
];
?>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ======= SIDEBAR ======= -->
<aside class="sidebar" id="sidebar">

    <!-- Brand / Logo -->
    <div class="sidebar-brand" style="padding:1.25rem 1rem 1rem; border-bottom:1px solid rgba(255,255,255,0.08); overflow:hidden;">
        <a href="../index.php" class="d-flex align-items-center gap-2 text-decoration-none" style="min-width:0;">
            <img src="../assets/img/logo.png" alt="Si-TANGKAL"
                 style="height:32px; width:32px; flex-shrink:0; object-fit:contain;">
            <div style="min-width:0; flex:1; overflow:hidden;">
                <div style="color:#fff; font-weight:700; font-size:0.9rem; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Si-TANGKAL</div>
                <div style="color:rgba(255,255,255,0.5); font-size:0.67rem; font-weight:400; white-space:nowrap;">Kota Cimahi</div>
            </div>
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav style="padding:1rem 0.75rem; flex:1;">
        <div style="font-size:0.65rem; font-weight:600; letter-spacing:1px; color:rgba(255,255,255,0.35); padding:0 0.5rem 0.5rem; text-transform:uppercase;">Menu Utama</div>
        <ul class="list-unstyled mb-0">
            <?php foreach ($menuItems as $item): ?>
            <?php $isActive = ($activePage === $item['key']); ?>
            <li class="mb-1">
                <a href="<?= $item['href'] ?>"
                   class="d-flex align-items-center gap-3 px-3 py-2 rounded-2 text-decoration-none sidebar-link <?= $isActive ? 'active' : '' ?>"
                   style="
                       color: <?= $isActive ? '#fff' : 'rgba(255,255,255,0.72)' ?>;
                       background: <?= $isActive ? 'rgba(255,255,255,0.12)' : 'transparent' ?>;
                       font-weight: <?= $isActive ? '600' : '400' ?>;
                       transition: all 0.18s ease;
                   ">
                    <i class="bi <?= $item['icon'] ?>" style="font-size:1.05rem; width:20px; text-align:center; flex-shrink:0;"></i>
                    <span style="font-size:0.875rem;"><?= $item['label'] ?></span>
                    <?php if ($isActive): ?>
                    <span class="ms-auto" style="width:5px; height:5px; border-radius:50%; background:#4ade80; flex-shrink:0;"></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Divider -->
        <div style="border-top:1px solid rgba(255,255,255,0.08); margin:1rem 0;"></div>

        <!-- Menu Peta (Eksternal) -->
        <!--<ul class="list-unstyled mb-0">
            <li class="mb-1">                
                <a href="../map.php" target="_blank"
                   class="d-flex align-items-center gap-3 px-3 py-2 rounded-2 text-decoration-none"
                   style="color:rgba(255,255,255,0.72); transition:all 0.18s ease;">
                    <i class="bi bi-map" style="font-size:1.05rem; width:20px; text-align:center;"></i>
                    <span style="font-size:0.875rem;">Peta</span>
                    <i class="bi bi-arrow-up-right ms-auto" style="font-size:0.7rem; opacity:0.5;"></i>
                </a>
            </li>
        </ul>-->
    </nav>

    <!-- Profile & Logout (Bottom) -->
    <div style="padding:0.75rem; border-top:1px solid rgba(255,255,255,0.08); margin-top:auto;">
        <!-- Profile Link -->
        <a href="profile.php"
           class="d-flex align-items-center gap-3 px-3 py-2 rounded-2 text-decoration-none mb-2 <?= ($activePage === 'profile') ? 'active' : '' ?>"
           style="
               color: <?= ($activePage === 'profile') ? '#fff' : 'rgba(255,255,255,0.72)' ?>;
               background: <?= ($activePage === 'profile') ? 'rgba(255,255,255,0.12)' : 'transparent' ?>;
               transition: all 0.18s ease;
           ">
            <!-- Avatar Inisial -->
            <div style="
                width:36px; height:36px; border-radius:50%;
                background:linear-gradient(135deg, #059669, #047857);
                display:flex; align-items:center; justify-content:center;
                color:#fff; font-weight:700; font-size:0.85rem; flex-shrink:0;
            ">
                <?= strtoupper(substr($_SESSION['admin']['Name'] ?? 'A', 0, 1)) ?>
            </div>
            <div style="min-width:0; flex:1;">
                <div style="color:#fff; font-weight:600; font-size:0.8rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= $adminName ?>
                </div>
                <div style="color:rgba(255,255,255,0.45); font-size:0.68rem;"><?= $adminType ?></div>
            </div>
            <i class="bi bi-pencil-square" style="font-size:0.8rem; color:rgba(255,255,255,0.4);"></i>
        </a>

        <!-- Logout Button -->
        <a href="../logout.php"
           class="d-flex align-items-center justify-content-center gap-2 w-100 py-2 rounded-2 text-decoration-none"
           style="background:rgba(220,53,69,0.15); color:#ff6b7a; font-size:0.8rem; font-weight:500; transition:all 0.18s ease;"
           onclick="return confirm('Yakin ingin keluar?')"
           onmouseover="this.style.background='rgba(220,53,69,0.25)'"
           onmouseout="this.style.background='rgba(220,53,69,0.15)'">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

</aside>

<!-- ======= MAIN WRAPPER ======= -->
<div class="main-wrapper">

    <!-- Topbar -->
    <div class="topbar">
        <!-- Mobile Toggle -->
        <button class="btn btn-sm btn-light border d-lg-none me-3" id="sidebarToggle">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="page-title">
            <i class="bi <?= $menuItems[array_search($activePage, array_column($menuItems, 'key'))] ['icon'] ?? 'bi-house' ?> me-2 text-success"></i>
            <?= $pageTitle ?? 'Dashboard' ?>
        </span>
        <!-- Topbar Right: Date -->
        <small class="text-muted d-none d-md-block">
            <i class="bi bi-calendar3 me-1"></i>
            <?= date('l, d F Y') ?>
        </small>
    </div>

    <!-- Main Content area opened here; closed in footer.php -->
    <div class="main-content">

<style>
.sidebar-link:hover {
    background: rgba(255,255,255,0.08) !important;
    color: #fff !important;
}
</style>
