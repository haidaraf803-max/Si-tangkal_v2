<?php
require_once __DIR__ . '/includes/config.php';
// Auth sudah otomatis dimuat oleh config.php di atas (lihat includes/auth.php)
require_once __DIR__ . '/includes/TreeRepository.php';

$pageTitle = 'Beranda';
$activeNav = 'home';
$stats = TreeRepository::stats();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div id="map"></div>

<!-- Left stack: Summary + Legend -->
<div class="left-stack scrollbar-thin">
    <div class="panel">
        <div class="panel-title">Ringkasan Data</div>
        <div class="panel-subtitle">Update terakhir: <?= date('d M Y') ?></div>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon total">🌳</div>
                <div>
                    <div class="summary-text-label">Total Pohon</div>
                    <div class="summary-text-value"><?= number_format($stats['total_trees'], 0, ',', '.') ?> <span>pohon</span></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon sehat">🌱</div>
                <div>
                    <div class="summary-text-label">Total Pohon Sehat</div>
                    <div class="summary-text-value"><?= number_format($stats['sehat_trees'], 0, ',', '.') ?> <span>pohon</span></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon kurang-sehat">🥀</div>
                <div>
                    <div class="summary-text-label">Total Pohon Kurang Sehat</div>
                    <div class="summary-text-value"><?= number_format($stats['kurang_sehat_trees'], 0, ',', '.') ?> <span>pohon</span></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon sakit">🍂</div>
                <div>
                    <div class="summary-text-label">Total Pohon Sakit</div>
                    <div class="summary-text-value"><?= number_format($stats['sakit_trees'] ?? 0, 0, ',', '.') ?> <span>pohon</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-title">Legenda</div>
        <div class="legend-list" style="margin-top:10px;">
            <div class="legend-item"><span class="legend-swatch sehat"></span> Pohon Sehat</div>
            <div class="legend-item"><span class="legend-swatch kurang-sehat"></span> Pohon Kurang Sehat</div>
            <div class="legend-item"><span class="legend-swatch sakit"></span> Pohon Sakit</div>
            <div class="legend-item"><span class="legend-swatch square green"></span> Ruang Terbuka Hijau</div>
            <div class="legend-item"><span class="legend-swatch outline"></span> Batas Kelurahan</div>
        </div>
    </div>
</div>

<!-- Right: Layer control panel -->
<div class="layer-panel" id="layer-panel">
    <div class="panel">
        <div class="layer-panel-header">
            <div class="panel-title" style="margin-bottom:0;">Layer Peta</div>
            <button class="panel-close" onclick="closeLayerPanel()">✕</button>
        </div>
        <!-- Group: Basemap (jenis peta dasar) -->
        <div class="layer-group" id="group-basemap">
            <div class="layer-group-header" onclick="toggleLayerGroup('group-basemap')">
                <span>Jenis Peta</span>
                <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
            <div class="layer-group-body">
                <div class="basemap-options">
                    <label class="basemap-option">
                        <input type="radio" name="basemap" id="basemap-osm" value="osm" checked>
                        <span class="basemap-thumb basemap-thumb-osm"></span>
                        <span class="basemap-label">Jalan</span>
                    </label>
                    <label class="basemap-option">
                        <input type="radio" name="basemap" id="basemap-satellite" value="satellite">
                        <span class="basemap-thumb basemap-thumb-satellite"></span>
                        <span class="basemap-label">Satelit</span>
                    </label>
                    <label class="basemap-option">
                        <input type="radio" name="basemap" id="basemap-light" value="light">
                        <span class="basemap-thumb basemap-thumb-light"></span>
                        <span class="basemap-label">Terang</span>
                    </label>
                    <label class="basemap-option">
                        <input type="radio" name="basemap" id="basemap-dark" value="dark">
                        <span class="basemap-thumb basemap-thumb-dark"></span>
                        <span class="basemap-label">Gelap</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Group: Pohon GeoServer (WMS, citra raster) -->
        <div class="layer-group" id="group-geoserver">
            <div class="layer-group-header" onclick="toggleLayerGroup('group-geoserver')">
                <span>GeoServer</span>
                <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
            <div class="layer-group-body">
                <!-- <div class="layer-row indented">
                    <span class="layer-row-label"><span class="layer-dot" style="background:var(--color-primary-dark);"></span> Pohon</span>
                    <label class="switch"><input type="checkbox" id="layer-wms-pohon"><span class="switch-slider"></span></label>
                </div> -->
                <div class="layer-row indented">
                    <span class="layer-row-label"><span class="layer-dot" style="background:var(--color-rw);"></span> Pohon RW</span>
                    <label class="switch"><input type="checkbox" id="layer-wms-pohon-rw"><span class="switch-slider"></span></label>
                </div>
                <div class="layer-row indented">
                    <span class="layer-row-label"><span class="layer-dot" style="background:var(--color-kahati);"></span> Pohon Kahati</span>
                    <label class="switch"><input type="checkbox" id="layer-wms-pohon-kahati"><span class="switch-slider"></span></label>
                </div>
            </div>
        </div>

        <!-- Group: Pohon Database (marker, bisa diklik) -->
        <div class="layer-group" id="group-database">
            <div class="layer-group-header" onclick="toggleLayerGroup('group-database')">
                <span>Data Pohon</span>
                <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
            <div class="layer-group-body">
                <div class="layer-row indented">
                    <span class="layer-row-label"><span class="layer-dot dual"></span> Pohon</span>
                    <label class="switch"><input type="checkbox" id="layer-db-pohon" ><span class="switch-slider"></span></label>
                </div>
            </div>
        </div>

        <div class="layer-row">
            <span class="layer-row-label"><span class="layer-dot" style="background:var(--color-green-space);"></span> Ruang Terbuka Hijau</span>
            <label class="switch"><input type="checkbox" id="layer-green" ><span class="switch-slider"></span></label>
        </div>
        <!-- <div class="layer-row">
            <span class="layer-row-label"><span class="layer-dot" style="border:2px dashed var(--color-gray-500); background:transparent;"></span> Batas Kelurahan</span>
            <label class="switch"><input type="checkbox" id="layer-villages" ><span class="switch-slider"></span></label>
        </div> -->
        <div class="layer-row">
            <span class="layer-row-label"><span class="layer-dot" style="border:2px dashed var(--color-primary-dark); background:transparent;"></span> Batas Kelurahan</span>
            <label class="switch"><input type="checkbox" id="layer-districts"><span class="switch-slider"></span></label>
        </div>
        <div class="layer-row">
            <span class="layer-row-label"><span class="layer-dot" style="border:2px dashed var(--color-primary-dark); background:transparent;"></span> Fotoudara</span>
            <label class="switch"><input type="checkbox" id="layer-fotoudara" ><span class="switch-slider"></span></label>
        </div>
        <div class="layer-row">
            <span class="layer-row-label"><span class="layer-dot" style="border:2px dashed var(--color-primary-dark); background:transparent;"></span> Pucuk</span>
            <label class="switch"><input type="checkbox" id="layer-pucuk" ><span class="switch-slider"></span></label>
        </div>
        <!-- <div class="layer-row">
            <span class="layer-row-label"><span class="layer-dot" style="background:var(--color-gray-300);"></span> Jalan</span>
            <label class="switch"><input type="checkbox" id="layer-roads" checked><span class="switch-slider"></span></label>
        </div>
        <div class="layer-row">
            <span class="layer-row-label">Aa Label Lokasi</span>
            <label class="switch"><input type="checkbox" id="layer-labels" checked><span class="switch-slider"></span></label>
        </div> -->
        <!-- <button class="btn-manage-layers" onclick="openManageLayerOrder()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"></line><line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="18" x2="20" y2="18"></line></svg>
            Atur Urutan Layer
        </button> -->
    </div>
</div>

<!-- Far right floating action buttons -->
<div class="fab-stack">
    <button class="fab" id="fab-layer" onclick="toggleLayerPanel()" title="Layer">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
        <span>Layer</span>
    </button>
    <button class="fab" id="fab-filter" onclick="openFilter()" title="Filter">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
        <span>Filter</span>
    </button>
    <!-- <button class="fab" id="fab-stats" onclick="openStatistics()" title="Statistik">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        <span>Statistik</span>
    </button> -->
    <button class="fab" id="fab-mylocation" onclick="locateMe()" title="Lokasi Saya">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 2v4M12 18v4M2 12h4M18 12h4"></path></svg>
        <span>Lokasi</span>
    </button>
    <button class="fab" id="fab-help" onclick="openHelp()" title="Bantuan">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 115.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        <span>Bantuan</span>
    </button>
</div>

<!-- Bottom info strip -->
<!-- <div class="info-strip glass">
    <div class="info-item">
        <div class="info-icon">✔</div>
        <div><div class="info-text-title">Data Terverifikasi</div><div class="info-text-sub">Validasi lapangan</div></div>
    </div>
    <div class="info-item">
        <div class="info-icon">⏱</div>
        <div><div class="info-text-title">Update Berkala</div><div class="info-text-sub">Data selalu terbaru</div></div>
    </div>
    <div class="info-item">
        <div class="info-icon">👥</div>
        <div><div class="info-text-title">Mudah Diakses</div><div class="info-text-sub">Informasi untuk semua</div></div>
    </div>
    <div class="info-item">
        <div class="info-icon">🌱</div>
        <div><div class="info-text-title">Dukung Lingkungan</div><div class="info-text-sub">Hijaukan Cimahi</div></div>
    </div>
</div> -->

<!-- Tree info popup card -->
<div class="tree-popup-card glass" id="tree-popup-card">
    <div class="tree-popup-header">
        <div class="tree-popup-tag">🌳 <span id="tp-category"></span></div>
        <button class="panel-close" onclick="closeTreePopup()">✕</button>
    </div>
    <img class="tree-popup-image" id="tp-image" src="" alt="Tree" onerror="this.src='https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?w=400'">
    <div class="tree-popup-body">
        <div class="tree-popup-name" id="tp-name"></div>
        <div class="tree-popup-sci" id="tp-sci"></div>
        <div class="tree-popup-meta">
            <div class="tree-popup-meta-row"><b>Lokasi</b> <span id="tp-address"></span></div>
            <div class="tree-popup-meta-row"><b>Kelurahan</b> <span id="tp-village"></span></div>
            <div class="tree-popup-meta-row"><b>Famili</b> <span id="tp-family"></span></div>
            <div class="tree-popup-meta-row"><b>Tahun Tanam</b> <span id="tp-tahun"></span></div>
            <div class="tree-popup-meta-row"><b>Umur Pohon</b> <span id="tp-umur"></span></div>
            <div class="tree-popup-meta-row"><b>Kondisi</b> <span class="badge" id="tp-condition"></span></div>
        </div>
        <a class="btn-view-detail" id="tp-detail-link" href="pages/tree-detail.php" onclick="openTreeDetail(event)">
            Lihat Detail
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
    </div>
</div>

<!-- Tree detail modal — dibuka lewat tombol "Lihat Detail" di popup card
     tanpa pindah halaman. Isinya diambil dari api/tree-detail.php (JSON)
     lalu dirender oleh openTreeDetail() di ui.js. Kalau butuh link yang bisa
     dibagikan/dibookmark, halaman penuhnya masih ada di pages/tree-detail.php. -->
<div class="modal-overlay" id="tree-detail-modal">
    <div class="modal-box modal-box-wide">
        <div class="modal-header">
            <h3 id="td-modal-title">Detail Pohon</h3>
            <button class="panel-close" onclick="closeTreeDetail()">✕</button>
        </div>
        <div id="td-modal-body">
            <div class="text-muted" style="padding:30px 0; text-align:center; font-size:13px;">Memuat data pohon…</div>
        </div>
    </div>
</div>

<!-- Filter modal -->
<div class="modal-overlay" id="filter-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Filter Data Pohon</h3>
            <button class="panel-close" onclick="closeFilterModal()">✕</button>
        </div>
        <div class="form-group">
            <label>Kesehatan Pohon</label>
            <div style="display:flex; gap:18px; margin-top:6px;">
                <label style="display:flex; align-items:center; gap:6px; font-size:13px;"><input type="checkbox" class="filter-kesehatan" value="Sehat" checked> Sehat</label>
                <label style="display:flex; align-items:center; gap:6px; font-size:13px;"><input type="checkbox" class="filter-kesehatan" value="Kurang Sehat" checked> Kurang Sehat</label>
                <label style="display:flex; align-items:center; gap:6px; font-size:13px;"><input type="checkbox" class="filter-kesehatan" value="Sakit" checked> Sakit</label>
            </div>
        </div>
        <div class="form-group">
            <label for="filter-status-kel">Status Konservasi (status_kel)</label>
            <select id="filter-status-kel">
                <option value="">Semua Status</option>
                <option value="Least Concern/Resiko Rendah">Least Concern / Resiko Rendah</option>
                <option value="Data Deficient/Kekurangan Data">Data Deficient / Kekurangan Data</option>
                <option value="Vulnerable/Rentan">Vulnerable / Rentan</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="resetFilter()">Reset</button>
            <button class="btn btn-outline" onclick="closeFilterModal()">Batal</button>
            <button class="btn btn-primary" onclick="applyFilter()">Terapkan Filter</button>
        </div>
    </div>
</div>

<!-- Manage layer order modal -->
<div class="modal-overlay" id="layer-order-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Atur Urutan Layer</h3>
            <button class="panel-close" onclick="closeLayerOrderModal()">✕</button>
        </div>
        <p class="text-muted" style="font-size:12.5px; margin-bottom:14px;">Seret untuk mengubah urutan tampilan layer pada peta (layer teratas akan ditampilkan paling depan).</p>
        <ul id="layer-order-list" style="display:flex; flex-direction:column; gap:8px;">
            <li style="background:var(--color-gray-50); padding:10px 14px; border-radius:10px; font-size:13px; cursor:grab;">🛰️ Pohon GeoServer (Semua)</li>
            <li style="background:var(--color-gray-50); padding:10px 14px; border-radius:10px; font-size:13px; cursor:grab;">🛰️ Pohon GeoServer RW</li>
            <li style="background:var(--color-gray-50); padding:10px 14px; border-radius:10px; font-size:13px; cursor:grab;">🛰️ Pohon GeoServer Kahati</li>
            <li style="background:var(--color-gray-50); padding:10px 14px; border-radius:10px; font-size:13px; cursor:grab;">🗄️ Pohon Database</li>
            <li style="background:var(--color-gray-50); padding:10px 14px; border-radius:10px; font-size:13px; cursor:grab;">🍃 Ruang Terbuka Hijau</li>
            <li style="background:var(--color-gray-50); padding:10px 14px; border-radius:10px; font-size:13px; cursor:grab;">📍 Batas Kelurahan</li>
            <li style="background:var(--color-gray-50); padding:10px 14px; border-radius:10px; font-size:13px; cursor:grab;">🛣 Jalan</li>
        </ul>
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="closeLayerOrderModal()">Simpan Urutan</button>
        </div>
    </div>
</div>

<script>window.__sitangkalStats = <?= json_encode($stats) ?>;</script>
<script>window.SITANGKAL_LOGGED_IN = <?= (class_exists('Auth') && Auth::isLoggedIn()) ? 'true' : 'false' ?>;</script>
<script>
    // Konfigurasi basemap dari config.php (satu-satunya sumber),
    // dibaca oleh assets/js/map.js — lihat BASEMAP_CONFIG di config.php.
    window.SITANGKAL_BASEMAPS = <?= json_encode(BASEMAP_CONFIG) ?>;
    window.SITANGKAL_DEFAULT_BASEMAP = <?= json_encode(DEFAULT_BASEMAP) ?>;
</script>

<!-- Tambah Monitoring modal — dipicu dari tombol "+ Monitoring" di modal
     Detail Pohon (lihat renderTreeDetail() di ui.js). Hanya bisa dipakai
     jika sudah login (lihat openMonitoringModal() di assets/js/monitoring.js). -->
<div class="modal-overlay" id="monitoring-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Tambah Monitoring — <span id="mon-tree-name"></span></h3>
            <button class="panel-close" onclick="closeMonitoringModal()">✕</button>
        </div>
        <form id="monitoring-form" onsubmit="return submitMonitoringForm(event)">
            <input type="hidden" name="pohon_id" id="mon-pohon-id">
            <input type="hidden" name="lat" id="mon-user-lat">
            <input type="hidden" name="lng" id="mon-user-lng">
            <input type="hidden" name="accuracy" id="mon-user-accuracy">

            <div class="form-group">
                <label for="mon-kesehatan">Kondisi Kesehatan</label>
                <select id="mon-kesehatan" name="kesehatan_monitoring" required>
                    <option value="">-- Pilih Kondisi --</option>
                    <option value="Sehat">Sehat</option>
                    <option value="Kurang Sehat">Kurang Sehat</option>
                    <option value="Sakit">Sakit</option>
                </select>
            </div>

            <div class="form-group">
                <label for="mon-umur">Umur Pohon (tahun)</label>
                <input type="text" id="mon-umur" name="umur_pohon" maxlength="4" inputmode="numeric" placeholder="mis. 5 (opsional)">
            </div>

            <div class="form-group">
                <label for="mon-catatan">Catatan</label>
                <textarea id="mon-catatan" name="catatan" rows="3" placeholder="Catatan hasil monitoring (opsional)"></textarea>
            </div>

            <div class="form-group">
                <label for="mon-files">Foto / Video Dokumentasi</label>
                <input type="file" id="mon-files" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi,.mkv">
            </div>

            <div id="mon-location-status" class="text-muted" style="font-size:12px; margin-bottom:12px;"></div>
            <div id="mon-error" class="alert alert-error" style="display:none; margin-bottom:12px;"></div>

            <button type="submit" class="btn btn-primary btn-block" id="mon-submit-btn" disabled>Simpan Monitoring</button>
        </form>
    </div>
</div>

<?php $extraJs = ['assets/js/data-service.js', 'assets/js/map.js', 'assets/js/ui.js', 'assets/js/monitoring.js']; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>