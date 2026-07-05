<?php
require_once __DIR__ . '/../includes/config.php';
// Auth sudah otomatis dimuat oleh config.php di atas (lihat includes/auth.php)
require_once __DIR__ . '/../includes/TreeRepository.php';

$id = (int)($_GET['id'] ?? 0);
$tree = TreeRepository::find($id);

if (!$tree) {
    http_response_code(404);
}

$pageTitle = $tree ? $tree['name'] : 'Pohon Tidak Ditemukan';
// header.php expects to live relative to root for asset links; we are in pages/ so adjust below manually instead of including header.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Si-TANGKAL</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="../assets/css/variables.css">
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/navbar.css">
<link rel="stylesheet" href="../assets/css/panels.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<nav class="navbar glass">
    <div class="navbar-left">
        <a href="../index.php" class="logo">
            <span class="logo-icon">🌳</span>
            <span class="logo-text">
                <span class="logo-title">Si-TANGKAL</span>
                <span class="logo-subtitle">Sistem Informasi Pohon Kota Cimahi</span>
            </span>
        </a>
    </div>
    <div class="navbar-right">
        <a href="../about.php" class="nav-link">Tentang</a>
        <a href="../login.php" class="nav-link">Dashboard</a>
        <a href="../login.php" class="btn-login">Login</a>
    </div>
</nav>

<div class="page-wrap">
    <div class="container">
        <a href="../index.php" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke peta
        </a>

        <?php if (!$tree): ?>
            <div class="card">
                <h2>Data pohon tidak ditemukan</h2>
                <p class="text-muted">ID pohon yang Anda cari tidak tersedia dalam basis data.</p>
            </div>
        <?php else: ?>
        <div class="detail-grid">
            <div>
                <img class="detail-image" src="../<?= htmlspecialchars($tree['image_url']) ?>" alt="<?= htmlspecialchars($tree['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?w=800'">
                <div id="mini-map" class="detail-mini-map"></div>
            </div>

            <div class="detail-info-card">
                <span class="badge <?= $tree['category'] === 'RW' ? 'baik' : 'sedang' ?>" style="margin-bottom:10px;">
                    <?= $tree['category'] === 'RW' ? 'Pohon RW' : 'Pohon Kahati' ?>
                </span>
                <h1><?= htmlspecialchars($tree['name']) ?></h1>
                <div class="sci-name"><?= htmlspecialchars($tree['scientific_name']) ?></div>

                <div class="detail-meta-grid">
                    <div class="detail-meta-item"><b>Alamat</b><?= htmlspecialchars($tree['address']) ?></div>
                    <div class="detail-meta-item"><b>Kelurahan</b><?= htmlspecialchars($tree['village']) ?></div>
                    <div class="detail-meta-item"><b>Kecamatan</b><?= htmlspecialchars($tree['district']) ?></div>
                    <div class="detail-meta-item"><b>Kondisi</b>
                        <span class="badge <?= strtolower(str_replace(' ', '-', $tree['condition'])) ?>"><?= htmlspecialchars($tree['condition']) ?></span>
                    </div>
                    <div class="detail-meta-item"><b>Famili</b><?= htmlspecialchars($tree['family'] ?: '-') ?></div>
                    <div class="detail-meta-item"><b>Habitus</b><?= htmlspecialchars($tree['habitus'] ?: '-') ?></div>
                    <div class="detail-meta-item"><b>Tahun Tanam</b><?= $tree['tahun_tanam'] ?? '-' ?></div>
                    <div class="detail-meta-item"><b>ID Pohon</b>#<?= $tree['id'] ?></div>
                </div>

                <div class="detail-meta-grid" style="margin-top:10px;">
                    <div class="detail-meta-item"><b>Serapan CO₂</b><?= $tree['serapan_co'] !== null ? $tree['serapan_co'] . ' kg/th' : '-' ?></div>
                    <div class="detail-meta-item"><b>Produksi O₂</b><?= $tree['produksi_o'] !== null ? $tree['produksi_o'] . ' kg/th' : '-' ?></div>
                    <div class="detail-meta-item"><b>Volume Kayu</b><?= $tree['volume'] !== null ? $tree['volume'] . ' m³' : '-' ?></div>
                    <div class="detail-meta-item"><b>Status Kesehatan</b><?= htmlspecialchars($tree['kesehatan'] ?? '-') ?></div>
                </div>

                <div>
                    <b style="font-size:11.5px; color:var(--color-gray-500); text-transform:uppercase; letter-spacing:0.3px;">Deskripsi</b>
                    <p class="detail-description"><?= htmlspecialchars($tree['description']) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($tree): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const lat = <?= json_encode($tree['lat']) ?>;
const lng = <?= json_encode($tree['lng']) ?>;
const miniMap = L.map('mini-map', { zoomControl: false, attributionControl: false }).setView([lat, lng], 16);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
L.marker([lat, lng]).addTo(miniMap);
</script>
<?php endif; ?>
</body>
</html>
