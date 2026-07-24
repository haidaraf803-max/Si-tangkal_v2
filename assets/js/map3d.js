/**
 * map3d.js — Si-TANGKAL Peta 3D (CesiumJS)
 * -----------------------------------------
 * Dipisah total dari map.js (Leaflet/peta 2D) karena Cesium butuh library,
 * container, dan model interaksi yang berbeda. Halaman map-3d.php hanya
 * memuat file ini, jadi tidak ada risiko file ini mengganggu peta 2D.
 *
 * Catatan: file ini pakai top-level `await` (mis. saat memuat terrain),
 * makanya WAJIB dimuat sebagai <script type="module"> (lihat footer.php /
 * $extraJsModules di map-3d.php).
 *
 * UI panel layer-nya dirender ke dalam #m3d-layer-groups memakai class yang
 * SAMA dengan panel layer peta 2D (.layer-group, .layer-row, .switch, dst
 * dari assets/css/panels.css) supaya tampilannya konsisten satu produk.
 */

// ======================= TOKEN =======================
// Token Cesium ion di bawah ini publik-terbatas (dipakai untuk mengambil
// aset 3D milik project ini dari akun Cesium ion) — sama seperti pada versi
// sebelumnya, bukan kredensial rahasia server. Kalau perlu dibatasi lebih
// ketat, atur pembatasan domain untuk token ini dari dashboard Cesium ion.
Cesium.Ion.defaultAccessToken =
  "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI5NmZjYzliNy1jMjBhLTQ3MzItOTRjZS0yYTdiNWIxYzU2NzQiLCJpZCI6NDE3ODU1LCJpYXQiOjE3NzYxMzcwNTd9.VTMAy29dv3ktNnWiaKRPbiMC9Ln_jkPE4tIcwjVfI_k";

const lod1AccessToken =
  "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI1MDdiMmJiNi1kOGYwLTRiOWUtODVjOC1mYzY2YmRhZWYyNGYiLCJpZCI6NDUyODcwLCJpc3MiOiJodHRwczovL2FwaS5jZXNpdW0uY29tIiwiYXVkIjoidW5kZWZpbmVkX2RlZmF1bHQiLCJpYXQiOjE3ODMzMDkwNjV9.qdVJERUqLU5nvmqIpvwVzrxtRJy1B78mdcD7lQfPf5I";
// const lod1AccessToken =
//   "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI3YzE3ZGYxOS1kOTgzLTRjODctOTEzNi1jNTFmMTk1YTgyMjUiLCJpZCI6MzE0NzQxLCJpYXQiOjE3NTA2NzAyNzB9.1BusO9iTg0OKy8ggDHjiqmFSdMJphg3ryyzh784m3Aw";
const groundAccessToken =
  "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiIyOTk0ODQyMC0zY2UyLTQzN2ItYjI4MC1iYjczYjBjNzY3Y2UiLCJpZCI6NDQ1NjAyLCJpc3MiOiJodHRwczovL2FwaS5jZXNpdW0uY29tIiwiYXVkIjoidW5kZWZpbmVkX2RlZmF1bHQiLCJpYXQiOjE3ODE2ODM5MDJ9.2CKYRL25KDPe5t8JSpHHYbz5RWCTi-0dHLzq2y4xcE0";

// ======================= VIEWER =======================
// Widget bawaan Cesium (timeline/animation/scene-mode/geocoder) dimatikan
// karena tidak relevan untuk model kota statis ini dan bikin toolbar penuh —
// kontrol layer sudah kita sediakan sendiri lewat panel di kanan.
const viewer = new Cesium.Viewer("cesiumContainer", {
  timeline: false,
  animation: false,
  baseLayerPicker: false,
  geocoder: false,
  sceneModePicker: false,
  navigationHelpButton: false,
  homeButton: true,
  fullscreenButton: true,
});

// ======================= CAMERA =======================
viewer.camera.setView({
  destination: Cesium.Cartesian3.fromDegrees(107.5413, -6.8841, 4000),
  orientation: {
    heading: Cesium.Math.toRadians(0),
    pitch: Cesium.Math.toRadians(-45),
    roll: 0,
  },
});

viewer.terrainProvider = await Cesium.CesiumTerrainProvider.fromIonAssetId(1);

// ======================= DATA LAYER =======================
const lod1Layers = {
  bangunan: { label: "Bangunan", color: "#fb923c", ids: [4960765], tilesets: [], loaded: false },
  water: { label: "Water", color: "#38bdf8", ids: [4960160], tilesets: [], loaded: false },
  jalan: { label: "Jalan", color: "#a8a29e", ids: [4960157], tilesets: [], loaded: false },
};

// Vegetasi dibuat grup terpisah dari LOD1/LOD2 (sejajar), sama seperti versi asal.
const vegetationLayers = {
  vegetasi: {
    label: "Vegetasi",
    color: "#84cc16",
    ids: [4957298],
    accessToken: lod1AccessToken,
    style: new Cesium.Cesium3DTileStyle({ color: "color('rgb(34,139,34)')" }),
    tilesets: [],
    loaded: false,
  },
  ground: {
    label: "Ground",
    // color: "#b5651d",
    color: "#2fb51d",
    ids: [4980589],
    accessToken: groundAccessToken,
    style: new Cesium.Cesium3DTileStyle({ color: "color('rgb(107,142,35)')" }),
    tilesets: [],
    loaded: false,
  },
};

const lod2Layers = {
  pohon: {
    label: "Pohon LOD2",
    color: "var(--color-primary)",
    ids: [4649049, 4649044, 4649043, 4649042, 4648998],
    source: "default",
    tilesets: [],
    loaded: false,
  },
  bangunan: {
    label: "Bangunan LOD2",
    color: "#c084fc",
    ids: [5069979],
    // 4972581, 4972580, 4972578, 4972576, 4972575, 4972574, 4972573, 4972571, 4972615, 4972747, 
    source: "lod1token",
    tilesets: [],
    loaded: false,
  },
};

// ======================= RENDER PANEL LAYER =======================
// Memakai ulang class .layer-group / .layer-row.indented / .switch dari
// panels.css (yang juga dipakai index.php) supaya tampilan panel layer
// peta 3D konsisten dengan peta 2D, bukan komponen baru yang beda gaya.
function renderGroup(groupId, title, badgeCount, entries, prefix) {
  const rows = entries
    .map(
      ([key, l]) => `
        <div class="layer-row indented" data-row="${prefix}-${key}">
          <span class="layer-row-label">
            <span class="layer-dot" style="background:${l.color};"></span>
            ${l.label}
          </span>
          <label class="switch">
            <input type="checkbox" data-layer="${prefix}-${key}">
            <span class="switch-slider"></span>
          </label>
        </div>`
    )
    .join("");

  return `
    <div class="layer-group collapsed" id="${groupId}">
      <div class="layer-group-header" onclick="toggleLayerGroup('${groupId}')">
        <span>${title} <span class="text-muted" style="font-weight:400;">(${badgeCount})</span></span>
        <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="layer-group-body">${rows}</div>
    </div>`;
}

function renderLayerControl() {
  const el = document.getElementById("m3d-layer-groups");
  if (!el) return;

  el.innerHTML =
    renderGroup("m3d-group-lod1", "📦 LOD 1", Object.keys(lod1Layers).length, Object.entries(lod1Layers), "lod1") +
    renderGroup("m3d-group-lod2", "🏢 LOD 2", Object.keys(lod2Layers).length, Object.entries(lod2Layers), "lod2") +
    renderGroup("m3d-group-vegetation", "🌿 Vegetasi", Object.keys(vegetationLayers).length, Object.entries(vegetationLayers), "vegetation");
}

renderLayerControl();

// ======================= HELPER: state loading per baris =======================
function setRowLoading(dataLayerKey, isLoading) {
  const row = document.querySelector(`[data-row="${dataLayerKey}"]`);
  if (row) row.classList.toggle("m3d-loading", isLoading);
  const checkbox = document.querySelector(`[data-layer="${dataLayerKey}"]`);
  if (checkbox) checkbox.disabled = isLoading;
}

// ======================= LOAD LOD1 =======================
async function loadLOD1Layer(key, checked) {
  const group = lod1Layers[key];
  const rowKey = `lod1-${key}`;

  if (!group.loaded && checked) {
    setRowLoading(rowKey, true);
    try {
      for (const id of group.ids) {
        const resource = await Cesium.IonResource.fromAssetId(id, { accessToken: lod1AccessToken });
        const tileset = await Cesium.Cesium3DTileset.fromUrl(resource);

        if (key === "water") {
          tileset.style = new Cesium.Cesium3DTileStyle({ color: "color('rgb(30, 144, 255)')" });
        }

        viewer.scene.primitives.add(tileset);
        group.tilesets.push(tileset);
      }
      group.loaded = true;
    } catch (e) {
      console.error(`Gagal memuat layer LOD1 "${key}"`, e);
    } finally {
      setRowLoading(rowKey, false);
    }
  }

  group.tilesets.forEach((t) => (t.show = checked));
}

// ======================= LOAD LOD2 =======================
async function loadLOD2Layer(layerKey, checked) {
  const group = lod2Layers[layerKey];
  const rowKey = `lod2-${layerKey}`;

  if (!group.loaded && checked) {
    setRowLoading(rowKey, true);
    try {
      for (const id of group.ids) {
        let tileset;

        if (group.source === "default") {
          tileset = await Cesium.Cesium3DTileset.fromIonAssetId(id);
        } else {
          const resource = await Cesium.IonResource.fromAssetId(id, { accessToken: lod1AccessToken });
          tileset = await Cesium.Cesium3DTileset.fromUrl(resource);
        }

        if (layerKey === "pohon") {
          tileset.style = new Cesium.Cesium3DTileStyle({ color: "color('rgb(34,139,34)')" });
        }
        if (layerKey === "bangunan") {
          tileset.style = new Cesium.Cesium3DTileStyle({ color: "color('rgb(220,220,220)')" });
        }

        viewer.scene.primitives.add(tileset);
        group.tilesets.push(tileset);
      }
      group.loaded = true;
    } catch (e) {
      console.error(`Gagal memuat layer LOD2 "${layerKey}"`, e);
    } finally {
      setRowLoading(rowKey, false);
    }
  }

  group.tilesets.forEach((t) => (t.show = checked));
}

// ======================= LOAD VEGETASI (grup terpisah) =======================
async function loadVegetationLayer(layerKey, checked) {
  const group = vegetationLayers[layerKey];
  const rowKey = `vegetation-${layerKey}`;
  if (!group) return;

  if (!group.loaded && checked) {
    setRowLoading(rowKey, true);
    try {
      for (const id of group.ids) {
        const resource = await Cesium.IonResource.fromAssetId(id, { accessToken: group.accessToken });
        const tileset = await Cesium.Cesium3DTileset.fromUrl(resource);

        if (group.style) tileset.style = group.style;

        viewer.scene.primitives.add(tileset);
        group.tilesets.push(tileset);
      }
      group.loaded = true;
    } catch (e) {
      console.error(`Gagal memuat layer vegetasi "${layerKey}"`, e);
    } finally {
      setRowLoading(rowKey, false);
    }
  }

  group.tilesets.forEach((t) => (t.show = checked));
}

// Data LOD2 / Vegetasi / LOD1 hanya dimuat saat switch-nya pertama kali
// dinyalakan (lazy load) — supaya buka halaman 3D tidak langsung menarik
// semua tileset sekaligus.
document.addEventListener("change", async (e) => {
  const layer = e.target.dataset.layer;
  if (!layer) return;
  const checked = e.target.checked;

  if (layer.startsWith("lod1-")) {
    await loadLOD1Layer(layer.replace("lod1-", ""), checked);
  } else if (layer.startsWith("lod2-")) {
    await loadLOD2Layer(layer.replace("lod2-", ""), checked);
  } else if (layer.startsWith("vegetation-")) {
    await loadVegetationLayer(layer.replace("vegetation-", ""), checked);
  }
});
