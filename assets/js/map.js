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
// Beberapa pilihan basemap gratis (tanpa API key) yang bisa dipilih user.
const baseLayers = {
    osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 22,
        maxNativeZoom: 19,
    }),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community',
        maxZoom: 22,
        maxNativeZoom: 19,
    }),
    light: L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 22,
        maxNativeZoom: 19,
    }),
    dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 22,
        maxNativeZoom: 19,
    }),
};

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

// Menyambungkan radio button pilihan basemap (id="basemap") di panel layer
// ke fungsi setBasemap(), dan memuat kembali pilihan terakhir user jika ada.
function bindBasemapToggle() {
    const radios = document.querySelectorAll('input[name="basemap"]');
    if (!radios.length) return;

    let saved = null;
    try { saved = localStorage.getItem('sitangkal_basemap'); } catch (e) { /* abaikan */ }
    const initial = (saved && baseLayers[saved]) ? saved : 'osm';

    radios.forEach((radio) => {
        radio.checked = (radio.value === initial);
        radio.addEventListener('change', () => {
            if (radio.checked) setBasemap(radio.value);
        });
    });

    return initial;
}

const layerGroups = {
    // Pohon — Database (marker, klikable, dari tabel `pohon` lewat api/trees.php)
    dbPohon: L.layerGroup(),
    // Pohon — GeoServer (citra WMS, bukan dari database lokal)
    wmsPohon: L.layerGroup(),
    wmsPohonRw: L.layerGroup(),
    wmsPohonKahati: L.layerGroup(),
    green: L.layerGroup(),
    villages: L.layerGroup(),
    districts: L.layerGroup(),
    roads: L.layerGroup(),
    labels: L.layerGroup(),
    wmsFotoudara: L.layerGroup(),
    wmsPucuk : L.layerGroup(),
};

// Ikon marker pohon berbentuk "pin lokasi" (bulat + lancip di bawah), digambar
// murni pakai CSS/emoji — tidak memuat file gambar, jadi rendering tetap cepat
// walau markernya banyak.
const TREE_SYMBOL = {
    sehat: '🌳',
    'kurang-sehat': '🥀',
    sakit: '🍂',
};

// Cache instance divIcon supaya tidak dibuat ulang setiap kali marker dirender.
const _treeIconCache = {};

function treeDivIcon(kesehatan) {
    const k = (kesehatan || '').toLowerCase();
    let cls = 'sehat';
    if (k === 'kurang sehat') {
        cls = 'kurang-sehat';
    } else if (k === 'sakit') {
        cls = 'sakit';
    }

    if (!_treeIconCache[cls]) {
        _treeIconCache[cls] = L.divIcon({
            html: `
                <div class="tree-pin ${cls}">
                    <span class="tree-pin-symbol">${TREE_SYMBOL[cls]}</span>
                </div>
            `,
            className: '',
            iconSize: [30, 40],
            iconAnchor: [15, 40],
            popupAnchor: [0, -38],
        });
    }
    return _treeIconCache[cls];
}

// ---------------- Pencarian & filter pohon (live, tanpa reload) ----------------
// Menyimpan kondisi pencarian/filter yang sedang aktif supaya search dan
// filter bisa digabung (mis. cari "jambu" + filter kesehatan "Sehat").
const treeQueryState = {
    q: '',
    kesehatan: [],
    status_kel: [],
};

async function refreshTreeLayer(overrides) {
    Object.assign(treeQueryState, overrides || {});
    try {
        const trees = await DataService.getTrees(treeQueryState);
        renderTrees(trees);
        return trees;
    } catch (e) {
        console.error('Gagal memuat data pohon', e);
        return [];
    }
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
    // kalau ada, kalau tidak default ke OpenStreetMap.
    const initialBasemap = bindBasemapToggle() || 'osm';
    setBasemap(initialBasemap);

    // Add default-on layers
    // layerGroups.dbPohon.addTo(map);
    // layerGroups.green.addTo(map);
    // layerGroups.villages.addTo(map);
    // // layerGroups.districts.addTo(map);
    // // layerGroups.labels.addTo(map);
    // layerGroups.wmsFotoudara.addTo(map);
  
    // WMS tree layers (wmsPohon / wmsPohonRw / wmsPohonKahati) and districts are
    // off by default — they're extra GeoServer overlays the user can opt into.

   loadAllLayers().then(() => {
        focusTreeFromUrl();
    });
    bindLayerToggles();
}

async function loadAllLayers() {
    // Muat semua data pohon dari database (tanpa filter) supaya langsung tampil semua.
    await refreshTreeLayer({ q: '', kesehatan: [], status_kel: [] });

    try {
        const pohonWms = await DataService.getPohonWMS();
        layerGroups.wmsPohon.clearLayers();
        layerGroups.wmsPohon.addLayer(pohonWms);
    } catch (e) { console.error('Pohon (WMS) load failed', e); }

    try {
        const pohonRwWms = await DataService.getPohonRWWMS();
        layerGroups.wmsPohonRw.clearLayers();
        layerGroups.wmsPohonRw.addLayer(pohonRwWms);
    } catch (e) { console.error('Pohon RW (WMS) load failed', e); }

    try {
        const pohonKahatiWms = await DataService.getPohonKahatiWMS();
        layerGroups.wmsPohonKahati.clearLayers();
        layerGroups.wmsPohonKahati.addLayer(pohonKahatiWms);
    } catch (e) { console.error('Pohon Kahati (WMS) load failed', e); }

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

    layerGroups.dbPohon.clearLayers();

    trees.forEach((tree) => {

        if (!tree.lat || !tree.lng) return;

        const marker = L.marker(
            [tree.lat, tree.lng],
            {
                icon: treeDivIcon(tree.kesehatan)
            }
        );

        marker.treeData = tree; // simpan data pohon di marker, dipakai focusTreeFromUrl()

        marker.on("click", () => {
            showTreePopup(tree, marker);
        });

        layerGroups.dbPohon.addLayer(marker);

    });

}

// function renderTrees(trees) {

//     layerGroups.dbPohon.clearLayers();

//     trees.forEach((tree) => {

//         if (!tree.lat || !tree.lng) return;

//         const marker = L.marker(
//             [tree.lat, tree.lng],
//             {
//                 icon: treeDivIcon(tree.kesehatan)
//             }
//         );

//         marker.on("click", () => {
//             showTreePopup(tree, marker);
//         });

//         layerGroups.dbPohon.addLayer(marker);

//     });

// }
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
    'layer-db-pohon'
];
// ---------------- Layer toggle binding ----------------
function bindLayerToggles() {

    const map_ = {
        'layer-wms-pohon': layerGroups.wmsPohon,
        'layer-wms-pohon-rw': layerGroups.wmsPohonRw,
        'layer-wms-pohon-kahati': layerGroups.wmsPohonKahati,
        'layer-db-pohon': layerGroups.dbPohon,
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
    if (!layerGroups.dbPohon.getLayers || map === undefined) return;

    const trees = await refreshTreeLayer({ q: q || '' });

    // Pastikan layer database pohon aktif supaya hasil pencarian terlihat.
    if (!map.hasLayer(layerGroups.dbPohon)) {
        layerGroups.dbPohon.addTo(map);
        const cb = document.getElementById('layer-db-pohon');
        if (cb) cb.checked = true;
    }

    // Arahkan peta ke hasil pencarian bila ada.
    const valid = trees.filter((t) => t.lat && t.lng);
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
    let found = null;

    layerGroups.dbPohon.eachLayer((marker) => {
        if (marker.treeData && marker.treeData.id === targetId) {
            found = marker;
        }
    });

    if (found) {
        if (!map.hasLayer(layerGroups.dbPohon)) {
            layerGroups.dbPohon.addTo(map);
            const cb = document.getElementById('layer-db-pohon');
            if (cb) cb.checked = true;
        }
        map.setView(found.getLatLng(), 18);
        found.fire('click');
    }
}

document.addEventListener('DOMContentLoaded', initMap);