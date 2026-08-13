/**
 * map.js — Si-TANGKAL interactive map
 * Initializes Leaflet map centered on Cimahi City, builds LayerGroups for
 * each spatial dataset, wires up clustering and the custom tree info card.
 */

const CIMAHI_CENTER = [-6.8743, 107.5425];
const INITIAL_ZOOM = 13;
const MAX_MAP_ZOOM = 22; // Zoom maksimal peta — dinaikkan agar bisa zoom lebih dekat

let map;

// ---------------- Basemap (peta dasar) ----------------
// Daftar & URL basemap TIDAK di-hardcode di sini — semuanya dibaca dari
// window.SITANGKAL_BASEMAPS, yang dikirim oleh maps.php dari config.php
// (BASEMAP_CONFIG). Jadi kalau mau menambah/mengubah basemap, cukup edit
// config.php — file ini otomatis mengikuti.
const BASEMAP_CONFIG = window.SITANGKAL_BASEMAPS || {};
const DEFAULT_BASEMAP = window.SITANGKAL_DEFAULT_BASEMAP || 'osm';

const baseLayers = {};
Object.entries(BASEMAP_CONFIG).forEach(([key, cfg]) => {
    baseLayers[key] = L.tileLayer(cfg.url, {
        attribution: cfg.attribution,
        maxZoom: cfg.maxZoom || MAX_MAP_ZOOM,
        maxNativeZoom: cfg.maxNativeZoom || 19,
    });
});

let activeBaseLayer = null;

// Ganti basemap yang sedang aktif. Layer sebelumnya dilepas, layer baru
// dipasang lalu didorong ke belakang supaya semua overlay (pohon, RTH, dll)
// tetap tampil di atasnya. Pilihan disimpan di localStorage supaya "diingat"
// saat user membuka peta lagi lain waktu.
function setBasemap(key) {
    if (!baseLayers[key] || !map) return;
    if (activeBaseLayer) {
        map.removeLayer(activeBaseLayer);
    }
    activeBaseLayer = baseLayers[key];
    activeBaseLayer.addTo(map);
    activeBaseLayer.bringToBack();
    try { localStorage.setItem('sitangkal_basemap', key); } catch (e) { /* abaikan jika storage diblokir */ }
}

// Menyambungkan radio button pilihan basemap (name="basemap") di panel layer
// ke fungsi setBasemap(), dan memuat kembali pilihan terakhir user jika ada.
function bindBasemapToggle() {
    const radios = document.querySelectorAll('input[name="basemap"]');
    if (!radios.length) return DEFAULT_BASEMAP;

    let saved = null;
    try { saved = localStorage.getItem('sitangkal_basemap'); } catch (e) { /* abaikan */ }
    const initial = (saved && baseLayers[saved]) ? saved : DEFAULT_BASEMAP;

    radios.forEach((radio) => {
        radio.checked = (radio.value === initial);
        radio.addEventListener('change', () => {
            if (radio.checked) setBasemap(radio.value);
        });
    });

    return initial;
}

const TREE_HEALTH = Object.freeze({
    'Sehat': { key: 'sehat', color: '#1e8a4c' },
    'Kurang Sehat': { key: 'kurang-sehat', color: '#d97706' },
    'Sakit': { key: 'sakit', color: '#d9534f' },
});
const ALL_TREE_HEALTH_STATUSES = Object.freeze(Object.keys(TREE_HEALTH));

// Mulai zoom 16 seluruh pohon ditampilkan sebagai point feature dengan
// koordinat mentah dari database. Di bawahnya pohon yang saling berdekatan
// diringkas menjadi cluster berisi angka.
const TREE_POINT_ZOOM = 16;
const TREE_CLUSTER_GRID_SIZE = 72;

// Semua point pohon menggunakan satu Canvas renderer bersama. Dengan ini
// Leaflet tidak membuat ribuan node HTML/gambar marker ketika pengguna zoom in.
const treePointRenderer = L.canvas({ padding: 0.5 });
let treeSourceTrees = [];
let treeDataLoaded = false;
let treeFetchSequence = 0;
const treeById = new Map();

const layerGroups = {
    // Pohon — Database (point vector / cluster, klikable, dari api/trees.php)
    dbPohon: L.layerGroup(),
    // Pohon — GeoServer (citra WMS, bukan dari database lokal)
    wmsPohon: L.layerGroup(),
    // Data GeoServer RW/Kahati dirender sebagai point vector, bukan WMS/PNG.
    pohonRw: L.layerGroup(),
    pohonKahati: L.layerGroup(),
    green: L.layerGroup(),
    villages: L.layerGroup(),
    districts: L.layerGroup(),
    roads: L.layerGroup(),
    labels: L.layerGroup(),
    wmsFotoudara: L.layerGroup(),
    wmsPucuk : L.layerGroup(),
};

// Kedua sumber berikut berasal dari GeoServer WFS, bukan dari database lokal.
// Data baru diambil ketika toggle layer diaktifkan supaya halaman awal ringan.
const referenceTreeSources = {
    rw: {
        key: 'rw',
        label: 'Pohon RW',
        clusterKind: 'rw',
        color: '#1e8a4c',
        layer: layerGroups.pohonRw,
        load: () => DataService.getPohonRWPoints(),
        trees: [],
        loaded: false,
        loading: null,
        visible: false,
    },
    kahati: {
        key: 'kahati',
        label: 'Pohon Kahati',
        clusterKind: 'kahati',
        color: '#1d7d8f',
        layer: layerGroups.pohonKahati,
        load: () => DataService.getPohonKahatiPoints(),
        trees: [],
        loaded: false,
        loading: null,
        visible: false,
    },
};

// ---------------- Pencarian & filter pohon (live, tanpa reload) ----------------
// Menyimpan kondisi pencarian/filter yang sedang aktif supaya search dan
// filter bisa digabung (mis. cari "jambu" + filter kesehatan "Sehat").
const treeQueryState = {
    q: '',
    kesehatan: [...ALL_TREE_HEALTH_STATUSES],
    status_kel: [],
};

function normalizeTreeHealth(value) {
    const health = String(value || '').trim().toLowerCase();
    if (health === 'kurang sehat') return 'Kurang Sehat';
    if (health === 'sakit') return 'Sakit';
    return 'Sehat';
}

function normalizeTreeHealthSelection(values) {
    const selected = Array.isArray(values) ? values : [];
    return ALL_TREE_HEALTH_STATUSES.filter((status) => selected.includes(status));
}

function hasValidTreeCoordinates(tree) {
    const lat = Number(tree?.lat);
    const lng = Number(tree?.lng);
    return Number.isFinite(lat) && Number.isFinite(lng) && lat !== 0 && lng !== 0;
}

function getVisibleTrees() {
    const selectedHealth = new Set(treeQueryState.kesehatan);
    return treeSourceTrees.filter((tree) => selectedHealth.has(normalizeTreeHealth(tree.kesehatan)));
}

function syncTreeHealthControls() {
    const selectedHealth = new Set(treeQueryState.kesehatan);

    document.querySelectorAll('[data-tree-health]').forEach((input) => {
        input.checked = selectedHealth.has(input.dataset.treeHealth);
    });
    document.querySelectorAll('.filter-kesehatan').forEach((input) => {
        input.checked = selectedHealth.has(input.value);
    });

    const masterToggle = document.getElementById('layer-db-pohon');
    if (masterToggle) {
        masterToggle.checked = selectedHealth.size > 0;
        masterToggle.indeterminate = selectedHealth.size > 0 && selectedHealth.size < ALL_TREE_HEALTH_STATUSES.length;
    }
}

function syncTreeLayerVisibility() {
    if (!map) return;
    if (treeQueryState.kesehatan.length > 0) {
        layerGroups.dbPohon.addTo(map);
    } else {
        map.removeLayer(layerGroups.dbPohon);
    }
}

function setTreeHealthSelection(values, options) {
    const shouldRender = !options || options.render !== false;
    treeQueryState.kesehatan = normalizeTreeHealthSelection(values);
    syncTreeHealthControls();
    syncTreeLayerVisibility();
    if (shouldRender) refreshTreePointPresentation();
}

async function refreshTreeLayer(overrides) {
    const next = overrides || {};
    const healthChanged = Object.prototype.hasOwnProperty.call(next, 'kesehatan');
    const sourceFilterChanged = Object.prototype.hasOwnProperty.call(next, 'q')
        || Object.prototype.hasOwnProperty.call(next, 'status_kel');

    if (Object.prototype.hasOwnProperty.call(next, 'q')) treeQueryState.q = next.q || '';
    if (Object.prototype.hasOwnProperty.call(next, 'status_kel')) treeQueryState.status_kel = next.status_kel || [];
    if (healthChanged) setTreeHealthSelection(next.kesehatan, { render: false });

    if (!treeDataLoaded || sourceFilterChanged) {
        const requestId = ++treeFetchSequence;
        try {
            // Kesehatan difilter di browser agar switch status bisa merespons
            // tanpa request ulang dan tanpa memuat ulang seluruh peta.
            const trees = await DataService.getTrees({
                q: treeQueryState.q,
                kesehatan: [],
                status_kel: treeQueryState.status_kel,
            });
            if (requestId !== treeFetchSequence) return getVisibleTrees();

            renderTrees(trees);
            treeDataLoaded = true;
        } catch (e) {
            console.error('Gagal memuat data pohon', e);
            return [];
        }
    } else {
        refreshTreePointPresentation();
    }

    syncTreeLayerVisibility();
    return getVisibleTrees();
}
// ---------------- Zoom control positioning ----------------
const ZOOM_HELP_GAP = 11;

function positionZoomBelowHelp() {
    const helpButton = document.getElementById('fab-help');
    const zoomCorner = document.querySelector('#map .leaflet-top.leaflet-left');
    const zoomControl = document.querySelector('#map .leaflet-control-zoom');

    if (!map || !helpButton || !zoomCorner || !zoomControl) return;

    const helpBox = helpButton.getBoundingClientRect();
    const mapBox = map.getContainer().getBoundingClientRect();
    const zoomStyle = window.getComputedStyle(zoomControl);

    const marginTop = parseFloat(zoomStyle.marginTop) || 0;
    const marginLeft = parseFloat(zoomStyle.marginLeft) || 0;

    zoomCorner.style.top =
        `${helpBox.bottom - mapBox.top + ZOOM_HELP_GAP - marginTop}px`;

    const helpCenterX = helpBox.left + (helpBox.width / 2);
    const zoomLeftX = helpCenterX - mapBox.left - (zoomControl.offsetWidth / 2);

    zoomCorner.style.left = `${zoomLeftX - marginLeft}px`;

    zoomCorner.style.right = 'auto';
    zoomCorner.style.bottom = 'auto';
}

function initMap() {
    map = L.map('map', {
        center: CIMAHI_CENTER,
        zoom: INITIAL_ZOOM,
        zoomControl: true,
        attributionControl: true,
        maxZoom: MAX_MAP_ZOOM,
    });

    // Basemap awal: pakai pilihan terakhir user (tersimpan di localStorage)
    // kalau ada, kalau tidak default ke DEFAULT_BASEMAP dari config.php.
    const initialBasemap = bindBasemapToggle();
    setBasemap(initialBasemap);

    // Panning tidak memicu render ulang. Susunan cluster hanya berubah saat
    // level zoom berubah, sehingga gerakan peta tetap terasa ringan.
    map.on('zoomend', refreshAllTreePointPresentations);

    // Add default-on layers
    // layerGroups.dbPohon.addTo(map);
    // layerGroups.green.addTo(map);
    // layerGroups.villages.addTo(map);
    // // layerGroups.districts.addTo(map);
    // // layerGroups.labels.addTo(map);
    // layerGroups.wmsFotoudara.addTo(map);
  
    // WMS pohon umum dan distrik dimatikan secara default. Pohon RW/Kahati
    // akan diambil sebagai point WFS hanya saat toggle-nya diaktifkan.

   loadAllLayers().then(() => {
        focusTreeFromUrl();
    });
    bindLayerToggles();
    requestAnimationFrame(positionZoomBelowHelp);
window.addEventListener('resize', positionZoomBelowHelp);
}

async function loadAllLayers() {
    // Muat semua data pohon dari database (tanpa filter) supaya langsung tampil semua.
    await refreshTreeLayer({ q: '', kesehatan: [...ALL_TREE_HEALTH_STATUSES], status_kel: [] });

    try {
        const pohonWms = await DataService.getPohonWMS();
        layerGroups.wmsPohon.clearLayers();
        layerGroups.wmsPohon.addLayer(pohonWms);
    } catch (e) { console.error('Pohon (WMS) load failed', e); }

   try {
        const FotoudarWms = await DataService.getFotoudara();
        layerGroups.wmsFotoudara.clearLayers();
        layerGroups.wmsFotoudara.addLayer(FotoudarWms);

    } catch (e) {
        console.error("Foto Udara gagal dimuat", e);
    }

    try {
        const pucukWms = await DataService.getPucuk();
        layerGroups.wmsPucuk.clearLayers();
        layerGroups.wmsPucuk.addLayer(pucukWms);

    } catch (e) {
        console.error("Pucuk gagal dimuat", e);
    }

    try {
        const green = await DataService.getGreenSpaces();
        renderGreenSpaces(green);
    } catch (e) { console.error('Green spaces load failed', e); }

    try {
        const villages = await DataService.getVillages();
        renderVillages(villages);
    } catch (e) { console.error('Villages load failed', e); }

    try {
        const districts = await DataService.getDistricts();
        renderDistricts(districts);
    } catch (e) { console.error('Districts load failed', e); }

    try {
        const roads = await DataService.getRoads();
        renderRoads(roads);
    } catch (e) { console.error('Roads load failed', e); }
}

function renderTrees(trees) {
    treeSourceTrees = Array.isArray(trees) ? trees.filter(hasValidTreeCoordinates) : [];
    treeById.clear();
    treeSourceTrees.forEach((tree) => treeById.set(Number(tree.id), tree));
    refreshTreePointPresentation();
}

function treePointOptions(tree) {
    const health = normalizeTreeHealth(tree.kesehatan);
    const style = TREE_HEALTH[health] || TREE_HEALTH.Sehat;
    return {
        renderer: treePointRenderer,
        radius: 5.5,
        color: '#ffffff',
        weight: 1.5,
        opacity: 1,
        fillColor: style.color,
        fillOpacity: 0.95,
        interactive: true,
    };
}

function addTreePoint(tree) {
    const point = L.circleMarker([tree.lat, tree.lng], treePointOptions(tree));
    point.treeData = tree;
    point.on('click', () => showTreePopup(tree, point));
    layerGroups.dbPohon.addLayer(point);
}

function clusterTrees(trees) {
    const cells = new Map();
    const zoom = map.getZoom();

    trees.forEach((tree) => {
        const pixel = map.project([tree.lat, tree.lng], zoom);
        const cellX = Math.floor(pixel.x / TREE_CLUSTER_GRID_SIZE);
        const cellY = Math.floor(pixel.y / TREE_CLUSTER_GRID_SIZE);
        const cellKey = `${cellX}:${cellY}`;

        if (!cells.has(cellKey)) {
            cells.set(cellKey, { trees: [], latTotal: 0, lngTotal: 0 });
        }

        const cell = cells.get(cellKey);
        cell.trees.push(tree);
        cell.latTotal += Number(tree.lat);
        cell.lngTotal += Number(tree.lng);
    });

    return Array.from(cells.values()).map((cell) => ({
        ...cell,
        lat: cell.latTotal / cell.trees.length,
        lng: cell.lngTotal / cell.trees.length,
    }));
}

function getClusterKind(trees) {
    const counts = { Sehat: 0, 'Kurang Sehat': 0, Sakit: 0 };
    trees.forEach((tree) => { counts[normalizeTreeHealth(tree.kesehatan)] += 1; });

    const activeStatuses = ALL_TREE_HEALTH_STATUSES.filter((status) => counts[status] > 0);
    if (activeStatuses.length > 1) return 'campuran';

    return (TREE_HEALTH[activeStatuses[0] || 'Sehat'] || TREE_HEALTH.Sehat).key;
}

function formatClusterCount(count) {
    return count > 999 ? '999+' : String(count);
}

function zoomToTreeCluster(cluster) {
    const bounds = L.latLngBounds(cluster.trees.map((tree) => [tree.lat, tree.lng]));
    if (!bounds.isValid()) return;

    const southWest = bounds.getSouthWest();
    const northEast = bounds.getNorthEast();
    const targetZoom = Math.min(TREE_POINT_ZOOM, map.getZoom() + 3);

    if (southWest.equals(northEast)) {
        map.setView([cluster.lat, cluster.lng], targetZoom);
        return;
    }

    map.fitBounds(bounds, { padding: [54, 54], maxZoom: TREE_POINT_ZOOM });
}

function addTreeCluster(cluster) {
    const count = cluster.trees.length;
    const kind = getClusterKind(cluster.trees);
    const icon = L.divIcon({
        html: `<div class="tree-cluster tree-cluster--${kind}" aria-label="${count} pohon"><span>${formatClusterCount(count)}</span></div>`,
        className: 'tree-cluster-marker',
        iconSize: [46, 46],
        iconAnchor: [23, 23],
    });
    const marker = L.marker([cluster.lat, cluster.lng], {
        icon,
        keyboard: true,
        title: `${count} pohon — klik untuk memperbesar`,
    });

    marker.on('click', () => zoomToTreeCluster(cluster));
    layerGroups.dbPohon.addLayer(marker);
}

function refreshTreePointPresentation() {
    if (!map) return;

    layerGroups.dbPohon.clearLayers();
    const visibleTrees = getVisibleTrees();
    if (!visibleTrees.length) return;

    // Pada zoom detail, setiap pohon adalah L.circleMarker (point vector Canvas).
    if (map.getZoom() >= TREE_POINT_ZOOM) {
        visibleTrees.forEach(addTreePoint);
        return;
    }

    clusterTrees(visibleTrees).forEach((cluster) => {
        if (cluster.trees.length === 1) {
            addTreePoint(cluster.trees[0]);
        } else {
            addTreeCluster(cluster);
        }
    });
}

function normalizeReferenceTreeFeatures(geojson, source) {
    const features = Array.isArray(geojson?.features) ? geojson.features : [];

    return features.map((feature, index) => {
        const coordinates = feature?.geometry?.coordinates;
        if (feature?.geometry?.type !== 'Point' || !Array.isArray(coordinates) || coordinates.length < 2) {
            return null;
        }

        const properties = (feature?.properties && typeof feature.properties === 'object')
            ? feature.properties
            : {};
        const rawId = properties.id ?? feature.id ?? index + 1;

        return {
            id: `${source.key}-${rawId}`,
            sourceKey: source.key,
            label: source.label,
            lat: Number(coordinates[1]),
            lng: Number(coordinates[0]),
            properties,
        };
    }).filter(hasValidTreeCoordinates);
}

function escapeMapHtml(value) {
    return String(value ?? '-').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);
}

function showReferenceTreePopup(tree) {
    const source = referenceTreeSources[tree.sourceKey];
    if (!source) return;

    const properties = tree.properties || {};
    const fields = source.key === 'rw'
        ? [
            ['Nomor Pohon', properties.NO_POHON],
            ['RW', properties.RW],
        ]
        : [
            ['FID Pohon', properties.FID_POHON],
            ['FID Taman', properties.FID_TAMAN],
            ['Keterangan', properties.KET],
        ];
    const details = fields
        .filter(([, value]) => value !== null && value !== undefined && String(value).trim() !== '')
        .map(([label, value]) => `<div><b>${escapeMapHtml(label)}:</b> ${escapeMapHtml(value)}</div>`)
        .join('');

    L.popup({ maxWidth: 280, offset: [0, -8] })
        .setLatLng([tree.lat, tree.lng])
        .setContent(`<div class="reference-tree-popup"><strong>${escapeMapHtml(tree.label)}</strong>${details ? `<div style="margin-top:6px; font-size:12px; line-height:1.55;">${details}</div>` : ''}</div>`)
        .openOn(map);
}

function referenceTreePointOptions(source) {
    return {
        renderer: treePointRenderer,
        radius: 5.5,
        color: '#ffffff',
        weight: 1.5,
        opacity: 1,
        fillColor: source.color,
        fillOpacity: 0.95,
        interactive: true,
    };
}

function addReferenceTreePoint(source, tree) {
    const point = L.circleMarker([tree.lat, tree.lng], referenceTreePointOptions(source));
    point.on('click', () => showReferenceTreePopup(tree));
    source.layer.addLayer(point);
}

function addReferenceTreeCluster(source, cluster) {
    const count = cluster.trees.length;
    const icon = L.divIcon({
        html: `<div class="tree-cluster tree-cluster--${source.clusterKind}" aria-label="${count} ${source.label}"><span>${formatClusterCount(count)}</span></div>`,
        className: 'tree-cluster-marker',
        iconSize: [46, 46],
        iconAnchor: [23, 23],
    });
    const marker = L.marker([cluster.lat, cluster.lng], {
        icon,
        keyboard: true,
        title: `${count} ${source.label} — klik untuk memperbesar`,
    });

    marker.on('click', () => zoomToTreeCluster(cluster));
    source.layer.addLayer(marker);
}

function refreshReferenceTreePointPresentation(source) {
    if (!map || !source.loaded || !source.visible) return;

    source.layer.clearLayers();
    if (!source.trees.length) return;

    if (map.getZoom() >= TREE_POINT_ZOOM) {
        source.trees.forEach((tree) => addReferenceTreePoint(source, tree));
        return;
    }

    clusterTrees(source.trees).forEach((cluster) => {
        if (cluster.trees.length === 1) {
            addReferenceTreePoint(source, cluster.trees[0]);
        } else {
            addReferenceTreeCluster(source, cluster);
        }
    });
}

function refreshAllTreePointPresentations() {
    refreshTreePointPresentation();
    Object.values(referenceTreeSources).forEach(refreshReferenceTreePointPresentation);
}

async function ensureReferenceTreeSource(sourceKey) {
    const source = referenceTreeSources[sourceKey];
    if (!source) return;

    if (source.loaded) {
        refreshReferenceTreePointPresentation(source);
        return source.trees;
    }

    if (!source.loading) {
        source.loading = source.load()
            .then((geojson) => {
                source.trees = normalizeReferenceTreeFeatures(geojson, source);
                source.loaded = true;
                refreshReferenceTreePointPresentation(source);
                return source.trees;
            })
            .catch((error) => {
                source.trees = [];
                source.loaded = false;
                console.error(`${source.label} WFS load failed`, error);
                throw error;
            })
            .finally(() => {
                source.loading = null;
            });
    }

    return source.loading;
}
// function renderGreenSpaces(geojson) {
//     const layer = L.geoJSON(geojson, {
//         style: {
//             color: '#5fa86f',
//             weight: 1.5,
//             fillColor: '#8fd19e',
//             fillOpacity: 0.45,
//         },
//         onEachFeature: (feature, lyr) => {
//             if (feature.properties?.name) {
//                 lyr.bindTooltip(feature.properties.name, { sticky: true });
//             }
//         },
//     });
//     layerGroups.green.addLayer(layer);
// }
function renderGreenSpaces(wmsLayer) {

    // Bersihkan layer lama jika ada
    layerGroups.green.clearLayers();

    // Tambahkan layer WMS ke LayerGroup
    layerGroups.green.addLayer(wmsLayer);

}

// function renderVillages(geojson) {
//     const layer = L.geoJSON(geojson, {
//         style: {
//             color: '#8a9590',
//             weight: 1.4,
//             dashArray: '5,4',
//             fillOpacity: 0,
//         },
//         onEachFeature: (feature, lyr) => {
//             if (feature.properties?.name) {
//                 lyr.bindTooltip(feature.properties.name, { sticky: true });
//             }
//         },
//     });
//     layerGroups.villages.addLayer(layer);

//     // Optional labels layer derived from village centroids
//     geojson.features.forEach((f) => {
//         try {
//             const center = L.geoJSON(f).getBounds().getCenter();
//             const label = L.marker(center, {
//                 icon: L.divIcon({
//                     className: 'village-label',
//                     html: `<div style="font-size:10px;font-weight:600;color:#4d564f;background:rgba(255,255,255,0.7);padding:1px 6px;border-radius:6px;">${f.properties.name}</div>`,
//                 }),
//                 interactive: false,
//             });
//             layerGroups.labels.addLayer(label);
//         } catch (e) { /* ignore malformed geometry */ }
//     });
// }
function renderVillages(wmsLayer) {

    layerGroups.villages.clearLayers();

    layerGroups.villages.addLayer(wmsLayer);

}


function renderDistricts(wmsLayer) {

    layerGroups.districts.clearLayers();

    layerGroups.districts.addLayer(wmsLayer);

}


function renderFotoudara(wmsLayer) {

    layerGroups.wmsFotoudara.clearLayers();

    layerGroups.wmsFotoudara.addLayer(wmsLayer);

}

function renderPucuk(wmsLayer){
    layerGroups.wmsPucuk.clearLayers();
    layerGroups.wmsPucuk.addLayer(wmsLayer);
}

function renderRoads(wmsLayer) {

    layerGroups.roads.clearLayers();

    layerGroups.roads.addLayer(wmsLayer);

}

// function renderRoads(geojson) {
//     const layer = L.geoJSON(geojson, {
//         style: { color: '#b9c2bd', weight: 2.5 },
//     });
//     layerGroups.roads.addLayer(layer);
// }

// ---------------- Tree info popup ----------------
function showTreePopup(tree, marker) {
    const card = document.getElementById('tree-popup-card');

    document.getElementById('tp-image').src = tree.image_url;
    document.getElementById('tp-category').textContent = tree.status_kel || 'Pohon';
    document.getElementById('tp-name').textContent = tree.name;
    document.getElementById('tp-sci').textContent = tree.scientific_name;
    document.getElementById('tp-address').textContent = tree.address;
    document.getElementById('tp-village').textContent = tree.village;
    document.getElementById('tp-family').textContent = tree.family || '-';
    document.getElementById('tp-tahun').textContent = tree.tahun_tanam || '-';
    document.getElementById('tp-umur').textContent = tree.umur_pohon ? (tree.umur_pohon + ' tahun') : '-';

    const condBadge = document.getElementById('tp-condition');
    const kesehatan = tree.kesehatan || '-';
    condBadge.textContent = kesehatan;
    const kLower = kesehatan.toLowerCase();
    let badgeCls = 'sehat';
    if (kLower === 'kurang sehat') badgeCls = 'kurang-sehat';
    else if (kLower === 'sakit') badgeCls = 'sakit';
    condBadge.className = 'badge ' + badgeCls;

    // href asli tetap diisi (buat middle-click / buka tab baru / share link),
    // tapi klik biasa akan ditangkap openTreeDetail() di ui.js dan membuka
    // modal di tempat tanpa pindah halaman.
    const tpDetailLink = document.getElementById('tp-detail-link');
    tpDetailLink.href = 'pages/tree-detail.php?id=' + tree.id;
    tpDetailLink.dataset.treeId = tree.id;

    // Posisi kartu ini SENGAJA tidak lagi mengikuti lokasi marker di peta
    // (dulu dihitung dari koordinat klik) — sekarang posisinya tetap, di
    // bawah tombol "Bantuan" (lihat .tree-popup-card di panels.css), supaya
    // selalu muncul di tempat yang sama & tidak pernah terpotong di tepi layar.
    card.classList.add('open');
}

function closeTreePopup() {
    document.getElementById('tree-popup-card').classList.remove('open');
}


const defaultLayers = [
    'layer-fotoudara',
    'layer-green',
];

function bindTreeHealthLayerToggles() {
    const masterToggle = document.getElementById('layer-db-pohon');
    const healthToggles = Array.from(document.querySelectorAll('[data-tree-health]'));

    if (!masterToggle || !healthToggles.length) {
        syncTreeLayerVisibility();
        return;
    }

    masterToggle.addEventListener('change', () => {
        // Switch induk adalah jalan pintas untuk menampilkan/menyembunyikan
        // seluruh tiga status kesehatan sekaligus.
        setTreeHealthSelection(masterToggle.checked ? ALL_TREE_HEALTH_STATUSES : []);
    });

    healthToggles.forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const selected = healthToggles
                .filter((input) => input.checked)
                .map((input) => input.dataset.treeHealth);
            setTreeHealthSelection(selected);
        });
    });

    syncTreeHealthControls();
    syncTreeLayerVisibility();
}

function bindReferenceTreeLayerToggles() {
    document.querySelectorAll('[data-reference-tree]').forEach((toggle) => {
        toggle.addEventListener('change', async () => {
            const source = referenceTreeSources[toggle.dataset.referenceTree];
            if (!source) return;

            if (!toggle.checked) {
                source.visible = false;
                map.removeLayer(source.layer);
                return;
            }

            source.visible = true;
            try {
                await ensureReferenceTreeSource(source.key);

                // Pengguna mungkin mematikan switch selama data WFS dimuat.
                if (toggle.checked) {
                    source.layer.addTo(map);
                }
            } catch (error) {
                source.visible = false;
                toggle.checked = false;
                map.removeLayer(source.layer);
            }
        });
    });
}

// ---------------- Layer toggle binding ----------------
function bindLayerToggles() {

    const map_ = {
        'layer-wms-pohon': layerGroups.wmsPohon,
        'layer-green': layerGroups.green,
        'layer-villages': layerGroups.villages,
        'layer-districts': layerGroups.districts,
        'layer-roads': layerGroups.roads,
        'layer-labels': layerGroups.labels,
        'layer-fotoudara': layerGroups.wmsFotoudara,
        'layer-pucuk' : layerGroups.wmsPucuk
    };

    Object.entries(map_).forEach(([id, group]) => {

        const checkbox = document.getElementById(id);
        if (!checkbox) return;

        // Tentukan kondisi awal dari JavaScript
        checkbox.checked = defaultLayers.includes(id);

        if (checkbox.checked) {
            group.addTo(map);
        }

        checkbox.addEventListener('change', () => {
            if (checkbox.checked) {
                group.addTo(map);
            } else {
                map.removeLayer(group);
            }
        });

    });

    bindTreeHealthLayerToggles();
    bindReferenceTreeLayerToggles();
}

// My Location button
function locateMe() {
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung oleh browser ini.');
        return;
    }
    navigator.geolocation.getCurrentPosition((pos) => {
        const latlng = [pos.coords.latitude, pos.coords.longitude];
        L.marker(latlng, {
            icon: L.divIcon({ className: 'my-location-marker', iconSize: [18, 18] }),
        }).addTo(map);
        map.setView(latlng, 16);
    }, () => {
        alert('Tidak dapat mengakses lokasi Anda.');
    });
}

// Dipanggil dari kotak pencarian navbar (lihat header.php) — tidak reload
// halaman, hanya refetch data pohon lalu render ulang layer database.
async function runTreeSearch(q) {
    if (map === undefined) return;

    const trees = await refreshTreeLayer({ q: q || '' });

    // Bila minimal satu kondisi kesehatan dipilih, layer dijaga tetap aktif.
    // Jika semua kondisi dimatikan pengguna, hasil pencarian sengaja tetap
    // disembunyikan sampai salah satu kondisi dinyalakan kembali.
    syncTreeLayerVisibility();

    // Arahkan peta ke hasil pencarian bila ada.
    const valid = trees.filter(hasValidTreeCoordinates);
    if (valid.length === 1) {
        map.setView([valid[0].lat, valid[0].lng], 17);
    } else if (valid.length > 1) {
        const bounds = L.latLngBounds(valid.map((t) => [t.lat, t.lng]));
        map.fitBounds(bounds, { padding: [60, 60], maxZoom: 16 });
    }
}

function focusTreeFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const treeId = params.get('tree_id');
    if (!treeId) return;

    const targetId = parseInt(treeId, 10);
    const tree = treeById.get(targetId);
    if (!tree) return;

    // Link detail harus tetap dapat membuka pohon meski sebelumnya statusnya
    // sedang tidak dipilih pada panel layer.
    const health = normalizeTreeHealth(tree.kesehatan);
    if (!treeQueryState.kesehatan.includes(health)) {
        setTreeHealthSelection([...treeQueryState.kesehatan, health], { render: false });
    }

    map.setView([tree.lat, tree.lng], TREE_POINT_ZOOM + 1);
    showTreePopup(tree);
}

document.addEventListener('DOMContentLoaded', initMap);
