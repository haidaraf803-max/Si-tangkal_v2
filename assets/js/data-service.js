/**
 * data-service.js
 * ----------------
 * Centralized data access layer for the map. Every spatial layer is fetched
 * through this service so that swapping local GeoJSON files for GeoServer
 * WMS/WFS endpoints later only requires editing the functions below —
 * nothing in map.js or ui.js needs to change.
 *
 * FUTURE GEOSERVER INTEGRATION:
 * Replace each `fetch('data/geojson/...')` call with a WFS GetFeature
 * request, e.g.:
 *   const url = `${GEOSERVER_BASE_URL}?service=WFS&version=2.0.0&request=GetFeature`
 *     + `&typeName=sitangkal:green_spaces&outputFormat=application/json`;
 * For raster/WMS layers, use L.tileLayer.wms(GEOSERVER_BASE_URL, {layers: 'sitangkal:...'})
 * instead of GeoJSON parsing.
 */
const DataService = (() => {

    const USE_GEOSERVER = true;

    const GEOSERVER = {
        WMS: 'https://c-map.cimahikota.go.id/geoserver/cimahi/wms',
        WFS: 'https://c-map.cimahikota.go.id/geoserver/ows'
    };

    // Opsi dasar untuk semua layer WMS GeoServer.
    //
    // PENTING soal maxZoom vs maxNativeZoom:
    // - maxZoom = 22 artinya peta BOLEH di-zoom sampai level 22.
    // - Tapi tanpa maxNativeZoom, Leaflet akan tetap MEMINTA tile WMS
    //   langsung ke GeoServer di level 20/21/22. Di level sedekat itu,
    //   bounding box per-tile jadi sangat kecil dan sering melebihi batas
    //   grid/cache (GeoWebCache) yang biasanya cuma disiapkan sampai
    //   zoom ~19 di GeoServer — hasilnya tile KOSONG/BLANK, sehingga data
    //   terlihat "hilang" saat di-zoom sangat dekat.
    // - Solusinya sama seperti basemap OSM di map.js: set maxNativeZoom
    //   ke level tertinggi yang benar-benar didukung GeoServer (19). Di atas
    //   level itu Leaflet otomatis MEMBESARKAN (upscale) tile terakhir yang
    //   valid, bukan meminta tile baru yang kosong — jadi data tidak hilang.
    const WMS_OPTIONS = {
        format: 'image/png',
        transparent: true,
        version: '1.1.1',
        tiled: true,
        maxZoom: 22,
        maxNativeZoom: 19, // <- FIX: cegah request WMS di zoom ekstrem yang bikin tile kosong
        // Simpan lebih banyak tile di sekitar viewport supaya saat peta
        // digeser (pan), tile tetangga sudah siap duluan dan tidak
        // sempat kosong/putih sebelum request baru selesai.
        keepBuffer: 4,
        // Jangan buang tile lama saat masih loading tile baru — mengurangi
        // efek "kedip hilang" saat geser cepat.
        updateWhenZooming: false,
    };

    // Beberapa nama layer GeoServer di project ini mengandung spasi
    // (mis. "SITANGKAL POHON"). Leaflet otomatis meng-encode parameter WMS,
    // jadi ini aman — tapi kita bungkus pembuatan layer WMS dalam satu
    // helper supaya semua layer konsisten dapat retry-on-error, tidak cuma
    // sebagian.
    function makeWmsLayer(layerName, extraOpts) {
        const layer = L.tileLayer.wms(GEOSERVER.WMS, {
            ...WMS_OPTIONS,
            ...(extraOpts || {}),
            layers: layerName,
        });

        // Retry otomatis kalau satu/dua tile gagal dimuat (mis. koneksi
        // lambat / GeoServer sempat sibuk saat peta digeser cepat-cepat).
        // Tanpa ini, tile yang gagal akan tetap kosong selamanya sampai
        // pan/zoom berikutnya memicu request ulang.
        layer.on('tileerror', (err) => {
            const tile = err.tile;
            if (!tile || tile.dataset.wmsRetried) return;
            tile.dataset.wmsRetried = '1';
            setTimeout(() => {
                const src = tile.src;
                tile.src = '';
                tile.src = src;
            }, 700);
        });

        return layer;
    }

    async function fetchJson(url) {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`Failed to load ${url}`);
        return res.json();
    }

    // WFS mengembalikan feature point dan koordinat asli. Ini berbeda dari
    // WMS yang hanya mengirim citra PNG, sehingga dapat digambar sebagai
    // L.circleMarker dan diringkas menjadi cluster angka di map.js.
    function getWfsPointFeatures(layerName) {
        const params = new URLSearchParams({
            service: 'WFS',
            version: '2.0.0',
            request: 'GetFeature',
            typeNames: layerName,
            outputFormat: 'application/json',
            srsName: 'EPSG:4326',
        });

        return fetchJson(`${GEOSERVER.WFS}?${params.toString()}`);
    }

    return {

  /**
   * Ambil data pohon dari database (api/trees.php).
   * opts (semua opsional):
   *   - q            : string pencarian bebas (nama_lokal, kesehatan, family)
   *   - kesehatan    : array nilai kesehatan yang dicentang (Sehat / Kurang Sehat)
   *   - status_kel   : array nilai status_kel yang dicentang
   * Jika opts tidak diberikan, q akan diambil dari query string URL saat ini
   * (?q=...) supaya link pencarian lama tetap berfungsi.
   */
  async getTrees(opts) {

    opts = opts || {};

    let q = opts.q;
    if (q === undefined) {
        const urlParams = new URLSearchParams(window.location.search);
        q = urlParams.get('q') || '';
    }

    const search = new URLSearchParams();

    if (q && q.trim() !== '') {
        search.set('q', q.trim());
    }

    (opts.kesehatan || []).forEach((v) => search.append('kesehatan[]', v));
    (opts.status_kel || []).forEach((v) => search.append('status_kel[]', v));

    const qs = search.toString();
    const url = 'api/trees.php' + (qs ? ('?' + qs) : '');

    return fetchJson(url);
},

        // ==========================
        // POHON (ALL) -> WMS
        // ==========================
        async getPohonWMS() {
            return makeWmsLayer('cimahi:SITANGKAL POHON');
        },

        // ==========================
        // POHON RW / KAHATI -> WFS GeoJSON point
        // ==========================
        async getPohonRWPoints() {
            return getWfsPointFeatures('cimahi:SITANGKAL POHON RW');
        },

        async getPohonKahatiPoints() {
            return getWfsPointFeatures('cimahi:SITANGKAL POHON KAHATI');
        },

        // ==========================
        // GREEN SPACE -> WMS
        // ==========================
        async getGreenSpaces() {

            if (!USE_GEOSERVER) {
                return fetchJson('data/geojson/green_spaces.geojson');
            }

            return makeWmsLayer('cimahi:SITANGKAL RTH');

        },

        // ==========================
// VILLAGES -> WMS
// ==========================
async getVillages() {

    if (!USE_GEOSERVER) {
        return fetchJson('data/geojson/villages.geojson');
    }

    return makeWmsLayer('cimahi:VILLAGES');

},

// ==========================
// DISTRICTS -> WMS
// ==========================
async getDistricts() {

    if (!USE_GEOSERVER) {
        return fetchJson('data/geojson/districts.geojson');
    }

    return makeWmsLayer('cimahi:ADMINISTRASI_AR');

},

// ==========================
// ROADS -> WMS
// ==========================
async getRoads() {

    if (!USE_GEOSERVER) {
        return fetchJson('data/geojson/roads.geojson');
    }

    return makeWmsLayer('cimahi:JALAN_LN');

},

// ==========================
// Fotoudara -> WMS
// ==========================
async getFotoudara() {

    return makeWmsLayer('cimahi:fotoudara');

},

// ==========================
// Fotoudara -> Pucuk
// ==========================

async getPucuk() {

    return makeWmsLayer('cimahi:pucuk');

},
    };

})();
