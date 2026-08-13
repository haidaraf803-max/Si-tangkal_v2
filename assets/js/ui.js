/**
 * ui.js — floating panel & FAB interactions for the homepage map view.
 */

function toggleLayerPanel() {
    document.getElementById('layer-panel').classList.toggle('open');
    setActiveFab('fab-layer');
}

function closeLayerPanel() {
    document.getElementById('layer-panel').classList.remove('open');
}

function setActiveFab(id) {
    document.querySelectorAll('.fab').forEach((f) => f.classList.remove('active'));
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
}

function openHelp() {
    alert('Si-TANGKAL membantu Anda menjelajahi data pohon kota di seluruh Kota Cimahi.\n\n- Gunakan ikon Layer untuk menampilkan/menyembunyikan data dan memilih kondisi kesehatan\n- Klik angka cluster untuk memperbesar peta\n- Klik point pohon untuk melihat detail');
}

function openStatistics() {
    const s = window.__sitangkalStats || {};
    alert(`Statistik Si-TANGKAL\n\nTotal Pohon: ${s.total_trees ?? '-'}\nTotal Pohon Sehat: ${s.sehat_trees ?? '-'}\nTotal Pohon Kurang Sehat: ${s.kurang_sehat_trees ?? '-'}`);
}

function openFilter() {
    document.getElementById('filter-modal').classList.add('open');
}
function closeFilterModal() {
    document.getElementById('filter-modal').classList.remove('open');
}

function applyFilter() {
    if (typeof refreshTreeLayer === 'undefined' || typeof map === 'undefined') return;

    const kesehatan = Array.from(document.querySelectorAll('.filter-kesehatan:checked')).map((el) => el.value);
    const statusKelValue = document.getElementById('filter-status-kel').value;
    const statusKel = statusKelValue ? [statusKelValue] : [];

    // Filter digabung dengan pencarian (q) yang mungkin sedang aktif di navbar.
    // refreshTreeLayer juga menyamakan pilihan ini dengan tiga switch pada
    // panel Layer Peta.
    refreshTreeLayer({ kesehatan, status_kel: statusKel });

    closeFilterModal();
}

// Mengembalikan tampilan ke semua data pohon (tanpa filter & tanpa kata kunci pencarian).
function resetFilter() {
    if (typeof refreshTreeLayer === 'undefined' || typeof map === 'undefined') return;

    document.querySelectorAll('.filter-kesehatan').forEach((el) => { el.checked = true; });
    document.getElementById('filter-status-kel').value = '';

    const searchInput = document.getElementById('navbar-search-input');
    if (searchInput) searchInput.value = '';

    const allKesehatan = (typeof ALL_TREE_HEALTH_STATUSES !== 'undefined')
        ? [...ALL_TREE_HEALTH_STATUSES]
        : ['Sehat', 'Kurang Sehat', 'Sakit'];
    refreshTreeLayer({ q: '', kesehatan: allKesehatan, status_kel: [] });
}

// Expand/collapse a layer group section (e.g. "Pohon GeoServer", "Pohon Database")
function toggleLayerGroup(groupId) {
    const group = document.getElementById(groupId);
    if (group) group.classList.toggle('collapsed');
}

function openManageLayerOrder() {
    document.getElementById('layer-order-modal').classList.add('open');
}
function closeLayerOrderModal() {
    document.getElementById('layer-order-modal').classList.remove('open');
}

// Drag reordering for the "Atur Urutan Layer" list
function initLayerOrderDrag() {
    const list = document.getElementById('layer-order-list');
    if (!list) return;
    let dragEl = null;

    list.querySelectorAll('li').forEach((item) => {
        item.draggable = true;
        item.addEventListener('dragstart', () => { dragEl = item; item.style.opacity = '0.4'; });
        item.addEventListener('dragend', () => { item.style.opacity = '1'; });
        item.addEventListener('dragover', (e) => e.preventDefault());
        item.addEventListener('drop', (e) => {
            e.preventDefault();
            if (dragEl && dragEl !== item) {
                const items = Array.from(list.children);
                const dragIdx = items.indexOf(dragEl);
                const dropIdx = items.indexOf(item);
                if (dragIdx < dropIdx) item.after(dragEl); else item.before(dragEl);
            }
        });
    });
}

// ---------------- Tree detail modal (popup, tanpa pindah halaman) ----------------
// Dipicu dari tombol "Lihat Detail" di tree-popup-card (lihat map.js -> showTreePopup).
// Datanya diambil dari api/tree-detail.php yang memakai TreeRepository::find(),
// jadi isinya sama lengkapnya dengan pages/tree-detail.php (halaman detail penuh).
let detailMiniMap = null;
let detailMiniMapMarker = null;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function openTreeDetail(event) {
    // Klik biasa dibuka sebagai modal. Middle-click / ctrl+click / cmd+click
    // (buka tab baru) tetap dibiarkan lewat ke href asli (pages/tree-detail.php).
    if (event) {
        if (event.button === 1 || event.ctrlKey || event.metaKey) return;
        event.preventDefault();
    }

    const link = document.getElementById('tp-detail-link');
    const id = link ? link.dataset.treeId : null;
    if (!id) return;

    closeTreePopup();

    const modal = document.getElementById('tree-detail-modal');
    const body = document.getElementById('td-modal-body');
    const title = document.getElementById('td-modal-title');

    title.textContent = 'Detail Pohon';
    body.innerHTML = '<div class="text-muted" style="padding:30px 0; text-align:center; font-size:13px;">Memuat data pohon…</div>';
    modal.classList.add('open');

    try {
        const res = await fetch('api/tree-detail.php?id=' + encodeURIComponent(id));
        const json = await res.json();

        if (!res.ok || !json.success) {
            body.innerHTML = '<div class="card"><h2>Data pohon tidak ditemukan</h2><p class="text-muted">Data mungkin sudah dihapus atau ID tidak valid.</p></div>';
            return;
        }

        renderTreeDetail(json.tree);
    } catch (e) {
        console.error('Gagal memuat detail pohon', e);
        body.innerHTML = '<div class="card"><h2>Gagal memuat data</h2><p class="text-muted">Terjadi masalah koneksi. Silakan coba lagi.</p></div>';
    }
}

function renderTreeDetail(tree) {
    const title = document.getElementById('td-modal-title');
    const body = document.getElementById('td-modal-body');

    title.textContent = tree.name || 'Detail Pohon';

    const categoryBadgeClass = tree.category === 'RW' ? 'baik' : 'sedang';
    const categoryLabel = tree.category === 'RW' ? 'Pohon RW' : 'Pohon Kahati';
    const conditionClass = (tree.condition || '').toLowerCase().replace(/\s+/g, '-');
    const hasCoords = tree.lat && tree.lng;

    // Gambar & mini-map lokasi SENGAJA disejajarkan (side-by-side) di baris
    // atas lewat .detail-modal-top, baru info lengkap di bawahnya full width
    // — sesuai permintaan supaya foto & lokasi bersebelahan.
    body.innerHTML = `
        <div class="detail-modal-top">
            <img class="detail-image" src="${escapeHtml(tree.image_url)}" alt="${escapeHtml(tree.name)}" onerror="this.src='https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?w=800'">
            ${hasCoords ? '<div id="td-mini-map" class="detail-mini-map"></div>' : ''}
        </div>
        <div class="detail-info-card" style="box-shadow:none; padding:0;">
            <span class="badge ${categoryBadgeClass}" style="margin-bottom:10px;">${escapeHtml(categoryLabel)}</span>
            <h1>${escapeHtml(tree.name)}</h1>
            <div class="sci-name">${escapeHtml(tree.scientific_name)}</div>

            <div class="detail-meta-grid">
                <div class="detail-meta-item"><b>Alamat</b>${escapeHtml(tree.address || '-')}</div>
                <div class="detail-meta-item"><b>Kelurahan</b>${escapeHtml(tree.village || '-')}</div>
                <div class="detail-meta-item"><b>Kecamatan</b>${escapeHtml(tree.district || '-')}</div>
                <div class="detail-meta-item"><b>Kondisi</b>
                    <span class="badge ${conditionClass}">${escapeHtml(tree.condition || '-')}</span>
                </div>
                <div class="detail-meta-item"><b>Famili</b>${escapeHtml(tree.family || '-')}</div>
                <div class="detail-meta-item"><b>Habitus</b>${escapeHtml(tree.habitus || '-')}</div>
                <div class="detail-meta-item"><b>Tahun Tanam</b>${escapeHtml(tree.tahun_tanam || '-')}</div>
                <div class="detail-meta-item"><b>ID Pohon</b>#${escapeHtml(tree.id)}</div>
            </div>

            <div class="detail-meta-grid" style="margin-top:10px;">
                <div class="detail-meta-item"><b>Serapan CO₂</b>${tree.serapan_co !== null && tree.serapan_co !== undefined ? escapeHtml(tree.serapan_co) + ' kg/th' : '-'}</div>
                <div class="detail-meta-item"><b>Produksi O₂</b>${tree.produksi_o !== null && tree.produksi_o !== undefined ? escapeHtml(tree.produksi_o) + ' kg/th' : '-'}</div>
                <div class="detail-meta-item"><b>Volume Kayu</b>${tree.volume !== null && tree.volume !== undefined ? escapeHtml(tree.volume) + ' m³' : '-'}</div>
                <div class="detail-meta-item"><b>Status Kesehatan</b>${escapeHtml(tree.kesehatan || '-')}</div>
            </div>

            <div>
                <b style="font-size:11.5px; color:var(--color-gray-500); text-transform:uppercase; letter-spacing:0.3px;">Deskripsi</b>
                <p class="detail-description">${escapeHtml(tree.description || 'Belum ada keterangan tambahan untuk pohon ini.')}</p>
            </div>

            <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--color-border, #e5e5e5);">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <b style="font-size:11.5px; color:var(--color-gray-500); text-transform:uppercase; letter-spacing:0.3px;">Riwayat Monitoring</b>
                </div>
                <div id="monitoring-history-list"></div>
            </div>
        </div>
    `;

    if (typeof loadMonitoringHistory === 'function') {
        loadMonitoringHistory(tree.id, 'monitoring-history-list');
    }

    if (hasCoords) {
        // #td-mini-map dibuat ULANG lewat innerHTML setiap kali modal dibuka,
        // jadi instance Leaflet lama (kalau ada) sudah menempel ke elemen DOM
        // yang sudah dibuang — harus di-remove() dulu lalu bikin instance baru
        // di elemen yang baru. Kalau tidak, mini-map akan blank/putih di
        // pembukaan modal kedua dan seterusnya.
        requestAnimationFrame(() => {
            if (detailMiniMap) {
                detailMiniMap.remove();
                detailMiniMap = null;
                detailMiniMapMarker = null;
            }
            detailMiniMap = L.map('td-mini-map', { zoomControl: false, attributionControl: false }).setView([tree.lat, tree.lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(detailMiniMap);
            detailMiniMapMarker = L.marker([tree.lat, tree.lng]).addTo(detailMiniMap);
            detailMiniMap.invalidateSize();
        });
    }
}

function closeTreeDetail() {
    document.getElementById('tree-detail-modal').classList.remove('open');
}

document.addEventListener('DOMContentLoaded', () => {
    initLayerOrderDrag();
    // Close tree popup when clicking the map background
    const mapEl = document.getElementById('map');
    if (mapEl) {
        mapEl.addEventListener('click', (e) => {
            if (e.target.id === 'map') closeTreePopup();
        });
    }
});
