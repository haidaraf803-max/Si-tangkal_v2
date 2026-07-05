<?php
/**
 * map-3d.php
 * ----------
 * Peta 3D Kota Cimahi (bangunan, jalan, water, vegetasi) menggunakan CesiumJS,
 * karena Leaflet (dipakai di index.php) tidak bisa menampilkan data 3D/LOD.
 *
 * Halaman ini SENGAJA dipisah dari index.php (peta 2D) supaya:
 *  - Library Cesium (besar, ~beberapa MB) cuma dimuat saat user benar-benar
 *    membuka Peta 3D, tidak membebani peta 2D.
 *  - Perubahan pada peta 3D tidak berisiko merusak logic Leaflet di map.js.
 *
 * UI (navbar, fab button, panel layer, switch toggle) sengaja memakai ulang
 * komponen yang sama persis dengan index.php (lihat includes/header.php,
 * assets/css/panels.css) supaya terasa satu produk yang sama, bukan halaman
 * asing yang ditempel.
 */
require_once __DIR__ . '/includes/config.php';
// Auth sudah otomatis dimuat oleh config.php di atas (lihat includes/auth.php)

$pageTitle = 'Peta 3D';
$activeNav = 'map3d';

// Peta 3D tidak butuh Leaflet sama sekali.
$skipLeaflet = true;

$extraCss = ['assets/css/map3d.css'];

// Cesium perlu dimuat di <head> (JS + CSS-nya sendiri) sebelum body dirender,
// jadi tidak bisa lewat mekanisme $extraJs biasa (yang naruh script di akhir
// body). header.php sudah disiapkan untuk menerima $extraHead mentah.
$extraHead = <<<HTML
<script src="https://cesium.com/downloads/cesiumjs/releases/1.135/Build/Cesium/Cesium.js"></script>
<link href="https://cesium.com/downloads/cesiumjs/releases/1.135/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
HTML;
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div id="cesiumContainer"></div>

<!-- Tombol aksi mengambang — pakai persis komponen .fab-stack / .fab yang
     sama dengan peta 2D (index.php) supaya konsisten. -->
<div class="fab-stack">
    <button class="fab active" id="fab-layer" onclick="toggleLayerPanel()" title="Layer">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
        <span>Layer</span>
    </button>
    <a class="fab" href="maps.php" title="Kembali ke Peta 2D">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <span>Peta 2D</span>
    </a>
</div>

<!-- Panel layer — struktur & class sama persis dengan #layer-panel di
     index.php (.panel, .layer-panel-header, .layer-group, .switch, dst).
     Isinya (grup LOD1 / LOD2 / Vegetasi) dirender oleh map3d.js karena
     jumlah layer & id asset Cesium ion bisa berubah-ubah. -->
<div class="layer-panel open" id="layer-panel">
    <div class="panel">
        <div class="layer-panel-header">
            <div class="panel-title" style="margin-bottom:0;">Layer Peta 3D</div>
            <button class="panel-close" onclick="closeLayerPanel()">✕</button>
        </div>
        <div id="m3d-layer-groups">
            <div class="text-muted" style="font-size:12.5px; padding:6px 4px;">Memuat daftar layer…</div>
        </div>
    </div>
</div>

<?php
$extraJs = ['assets/js/ui.js']; // reuse toggleLayerPanel()/closeLayerPanel()/toggleLayerGroup()
$extraJsModules = ['assets/js/map3d.js'];
include __DIR__ . '/includes/footer.php';
?>
