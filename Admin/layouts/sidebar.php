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

// ===== MENU DINAMIS BERBASIS RBAC =====
// Sidebar sekarang mengikuti tabel `menus` + `role_menu_access`
// (lihat Admin/core/Rbac.php & db/migration_rbac.sql), bukan array
// statis lagi — supaya menu yang tidak diizinkan untuk peran user
// otomatis tidak tampil (mis. Pelapor Pemangkasan tidak melihat
// Manajemen Pengguna / Stok Bibit).
require_once __DIR__ . '/../core/Rbac.php';

$menuItems = array_map(function ($m) {
    return [
        'href'  => $m['url'],
        'icon'  => $m['icon'],
        'label' => $m['label'],
        'key'   => $m['code'],
    ];
}, Rbac::accessibleMenus($config));

// ===== MENU YANG SEDANG DI-HOLD (disembunyikan sementara) =====
// "Pergantian Pohon" (Menu Penebangan) di-hold sesuai skema terbaru
// 20 Agustus 2026, poin 4. Cukup difilter dari sini (tidak menyentuh
// tabel `menus`/`role_menu_access`) supaya gampang dimunculkan lagi
// nanti — tinggal hapus/comment baris di bawah ini.
$menuKeysOnHold = ['pergantian_pohon'];
$menuItems = array_values(array_filter($menuItems, function ($m) use ($menuKeysOnHold) {
    return !in_array($m['key'], $menuKeysOnHold, true);
}));

// ===== PENGELOMPOKAN SIDEBAR (tampilan saja, tidak mengubah RBAC) =====
// Menu tetap sepenuhnya dikontrol oleh Rbac::accessibleMenus() di atas —
// mapping di bawah ini HANYA menentukan sub-menu mana yang dikelompokkan
// di bawah kategori mana pada tampilan sidebar. Kode menu ('key') yang
// tidak disebut di sini (atau yang usernya tidak punya akses) otomatis
// tidak muncul. Kalau nanti ada menu baru, tinggal tambah code-nya ke
// grup yang sesuai di bawah.
$menuGroupDefs = [
    'pemeliharaan' => [
        'label' => 'Pemeliharaan',
        'icon'  => 'bi-flower1',
        'codes' => ['laporan_penanaman', 'pemakaian_pupuk', 'pemakaian_bbm', 'permintaan_sarpras', 'pengajuan'],
    ],
    'penebangan' => [
        'label' => 'Penebangan',
        'icon'  => 'bi-scissors',
        'codes' => ['pergantian_pohon'],
    ],
    // "monitoring" (Monitoring Pohon, dulu "Pemeliharaan / Monitoring RTH")
    // dipindahkan ke grup Inventarisasi Pohon sesuai skema terbaru
    // 20 Agustus 2026, poin 5a. Label menu diubah lewat
    // db/migration_rename_monitoring_menu.sql — pemindahan di sini hanya
    // memengaruhi tampilan grup sidebar, tidak menyentuh RBAC.
    'inventarisasi' => [
        'label' => 'Inventarisasi Pohon',
        'icon'  => 'bi-tree',
        'codes' => ['pohon', 'monitoring', 'stok_bibit', 'permohonan_bibit'],
    ],
    'peta' => [
        'label' => 'Peta',
        'icon'  => 'bi-map',
        'codes' => ['map', 'peta_deliniasi'],
    ],
    'data' => [
        'label' => 'Penyajian Data',
        'icon'  => 'bi-bar-chart-line',
        'codes' => ['penyajian_data'],
    ],
    // Menu "Dokumen" (daftar dokumen + tombol unduh) sesuai skema
    // terbaru 20 Agustus 2026, poin 6a. Lihat db/migration_dokumen.sql
    // untuk pendaftaran menu & RBAC-nya.
    'dokumen' => [
        'label' => 'Dokumen',
        'icon'  => 'bi-file-earmark-arrow-down',
        'codes' => ['dokumen'],
    ],
    'akun' => [
        'label' => 'Pengelolaan Akun',
        'icon'  => 'bi-people',
        'codes' => ['users', 'roles'],
    ],
];

$menuItemsByKey = [];
foreach ($menuItems as $mi) {
    $menuItemsByKey[$mi['key']] = $mi;
}

// ===== (Fallback tanpa-DB sudah dihapus) =====
// laporan_penanaman, peta_deliniasi, dan penyajian_data sekarang sudah
// terdaftar resmi di tabel `menus` (lihat db/migration_add_missing_menus.sql),
// jadi $menuItems dari Rbac::accessibleMenus() di atas sudah otomatis
// mencakup ketiganya sesuai izin per-role masing-masing. Tidak perlu lagi
// disuntik manual di sini.

// 'dashboard' selalu tampil sendiri di atas, tidak masuk grup manapun
$groupedCodes = ['dashboard'];
foreach ($menuGroupDefs as $g) {
    $groupedCodes = array_merge($groupedCodes, $g['codes']);
}

// Susun grup akhir: hanya grup yang minimal punya 1 item yang boleh dilihat user ini
$sidebarGroups = [];
foreach ($menuGroupDefs as $groupKey => $g) {
    $items = [];
    foreach ($g['codes'] as $code) {
        if (isset($menuItemsByKey[$code])) {
            $items[] = $menuItemsByKey[$code];
        }
    }
    if ($items) {
        $sidebarGroups[$groupKey] = ['label' => $g['label'], 'icon' => $g['icon'], 'items' => $items];
    }
}

// Menu yang tidak masuk ke grup manapun (mis. menu baru yang belum dipetakan)
// tetap ditampilkan sebagai item lepas di bawah "Lainnya", supaya tidak
// pernah ada menu yang hilang hanya karena lupa dipetakan.
$looseItems = [];
foreach ($menuItems as $mi) {
    if (!in_array($mi['key'], $groupedCodes, true)) {
        $looseItems[] = $mi;
    }
}

// Grup mana yang harus otomatis terbuka (mengandung halaman aktif saat ini)
$activeGroupKey = null;
foreach ($sidebarGroups as $groupKey => $g) {
    foreach ($g['items'] as $it) {
        if ($it['key'] === $activePage) {
            $activeGroupKey = $groupKey;
            break 2;
        }
    }
}
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
    <nav style="padding:1rem 0.75rem; flex:1; overflow-y:auto;">
        <div style="font-size:0.65rem; font-weight:600; letter-spacing:1px; color:rgba(255,255,255,0.35); padding:0 0.5rem 0.5rem; text-transform:uppercase;">Menu Utama</div>

        <!-- Dashboard (selalu di atas, di luar grup) -->
        <?php if (isset($menuItemsByKey['dashboard'])): ?>
        <?php $item = $menuItemsByKey['dashboard']; $isActive = ($activePage === $item['key']); ?>
        <ul class="list-unstyled mb-1">
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
        </ul>
        <?php endif; ?>

        <!-- Grup Menu (Pemeliharaan, Penebangan, Inventarisasi, Peta, dst) -->
        <!-- Judul kategori + chevron, bisa dibuka/tutup. Grup yang berisi -->
        <!-- halaman aktif otomatis terbuka, sisanya tertutup (hemat tempat). -->
        <?php foreach ($sidebarGroups as $groupKey => $group): ?>
        <?php
            $groupHasActive = ($activeGroupKey === $groupKey);
            $collapseId     = 'menuGroup-' . $groupKey;
        ?>
        <div style="margin-top:0.35rem;">
            <a href="#<?= $collapseId ?>" data-bs-toggle="collapse" role="button"
               aria-expanded="<?= $groupHasActive ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>"
               class="d-flex align-items-center gap-2 text-decoration-none sidebar-group-toggle"
               style="padding:0.4rem 0.5rem;">
                <i class="bi <?= $group['icon'] ?>" style="font-size:0.8rem; color:rgba(255,255,255,0.5); width:16px; text-align:center; flex-shrink:0;"></i>
                <span style="font-size:0.7rem; font-weight:700; letter-spacing:0.5px; color:rgba(255,255,255,0.55); text-transform:uppercase; flex:1;"><?= htmlspecialchars($group['label']) ?></span>
                <i class="bi bi-chevron-down sidebar-chevron" style="font-size:0.65rem; color:rgba(255,255,255,0.4); transition:transform 0.2s ease; flex-shrink:0;"></i>
            </a>
            <div class="collapse <?= $groupHasActive ? 'show' : '' ?>" id="<?= $collapseId ?>">
                <ul class="list-unstyled mb-0" style="padding-top:0.15rem;">
                    <?php foreach ($group['items'] as $item): ?>
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
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($looseItems): ?>
        <div style="margin-top:0.35rem;">
            <?php
                $looseId = 'menuGroup-lainnya';
                $looseHasActive = false;
                foreach ($looseItems as $li) { if ($li['key'] === $activePage) { $looseHasActive = true; break; } }
            ?>
            <a href="#<?= $looseId ?>" data-bs-toggle="collapse" role="button"
               aria-expanded="<?= $looseHasActive ? 'true' : 'false' ?>" aria-controls="<?= $looseId ?>"
               class="d-flex align-items-center gap-2 text-decoration-none sidebar-group-toggle"
               style="padding:0.4rem 0.5rem;">
                <i class="bi bi-three-dots" style="font-size:0.8rem; color:rgba(255,255,255,0.5); width:16px; text-align:center; flex-shrink:0;"></i>
                <span style="font-size:0.7rem; font-weight:700; letter-spacing:0.5px; color:rgba(255,255,255,0.55); text-transform:uppercase; flex:1;">Lainnya</span>
                <i class="bi bi-chevron-down sidebar-chevron" style="font-size:0.65rem; color:rgba(255,255,255,0.4); transition:transform 0.2s ease; flex-shrink:0;"></i>
            </a>
            <div class="collapse <?= $looseHasActive ? 'show' : '' ?>" id="<?= $looseId ?>">
                <ul class="list-unstyled mb-0" style="padding-top:0.15rem;">
                    <?php foreach ($looseItems as $item): ?>
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
            </div>
        </div>
        <?php endif; ?>
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
        <!-- Topbar Right: Notifikasi + Tanggal -->
        <div class="d-flex align-items-center gap-3">
            <?php
            require_once __DIR__ . '/../core/NotificationModel.php';
            $notifModel   = new NotificationModel($config);
            $notifUserId  = (int) ($_SESSION['admin']['UserId'] ?? 0);
            $notifRoleId  = Rbac::currentRoleId($config);
            $notifUnread  = $notifModel->countUnread($notifUserId, $notifRoleId);
            $notifItems   = $notifModel->getForUser($notifUserId, $notifRoleId, 8);
            ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-light border position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <?php if ($notifUnread > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                        <?= $notifUnread > 9 ? '9+' : $notifUnread ?>
                    </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width:320px; max-height:400px; overflow-y:auto;">
                    <div class="px-3 py-2 border-bottom fw-600" style="font-size:0.8rem;">Notifikasi</div>
                    <?php if (empty($notifItems)): ?>
                    <div class="px-3 py-4 text-center text-muted" style="font-size:0.8rem;">Belum ada notifikasi</div>
                    <?php else: ?>
                        <?php foreach ($notifItems as $n): ?>
                        <a href="<?= htmlspecialchars($n['link'] ?: '#') ?>"
                           class="dropdown-item py-2 px-3 border-bottom <?= $n['is_read'] ? '' : 'bg-success-subtle' ?>"
                           style="white-space:normal; font-size:0.78rem;"
                           onclick="fetch('../api/notifications/mark_read.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id: <?= (int) $n['id'] ?>}), keepalive:true});">
                            <div class="fw-600"><?= htmlspecialchars($n['title']) ?></div>
                            <div class="text-muted"><?= htmlspecialchars($n['message']) ?></div>
                            <div class="text-muted" style="font-size:0.68rem;"><?= htmlspecialchars(date('d M Y H:i', strtotime($n['created_at']))) ?></div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <small class="text-muted d-none d-md-block">
                <i class="bi bi-calendar3 me-1"></i>
                <?= date('l, d F Y') ?>
            </small>
        </div>
    </div>

    <!-- Main Content area opened here; closed in footer.php -->
    <div class="main-content">

<style>
.sidebar-link:hover,
.sidebar-group-toggle:hover .sidebar-chevron,
.sidebar-group-toggle:hover span {
    color: #fff !important;
}
.sidebar-link:hover {
    background: rgba(255,255,255,0.08) !important;
}
.sidebar-group-toggle[aria-expanded="true"] .sidebar-chevron {
    transform: rotate(180deg);
}
</style>