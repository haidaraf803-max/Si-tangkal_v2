/**
 * assets/js/monitoring.js
 * ---------------------------------------------------------
 * Fitur "Tambah Monitoring" untuk halaman publik (peta & detail
 * pohon). Dipakai bersama oleh maps.php dan pages/tree-detail.php.
 *
 * Membutuhkan sebelum file ini di-load:
 *   - window.SITANGKAL_LOGGED_IN (boolean, dari PHP: Auth::isLoggedIn())
 *   - window.SITANGKAL_BASE (string, path relatif ke root project —
 *     '' jika halaman ada di root seperti maps.php, '../' jika di
 *     dalam subfolder seperti pages/tree-detail.php)
 *   - markup modal #monitoring-modal (lihat maps.php / tree-detail.php)
 *
 * Jarak maksimum ke pohon (meter) — divalidasi ulang di server,
 * ini hanya untuk feedback cepat ke user di browser.
 */
const MONITORING_MAX_DISTANCE_METERS = 1000;
const SITANGKAL_BASE = window.SITANGKAL_BASE || '';

let monitoringCurrentTree = null; // { id, name, lat, lng }

function distanceMeters(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function openMonitoringModal(id, name, lat, lng) {
    if (!window.SITANGKAL_LOGGED_IN) {
        window.location.href = SITANGKAL_BASE + 'login.php';
        return;
    }

    monitoringCurrentTree = { id, name, lat: lat ? parseFloat(lat) : null, lng: lng ? parseFloat(lng) : null };

    const modal = document.getElementById('monitoring-modal');
    if (!modal) return;

    document.getElementById('mon-tree-name').textContent = name || ('Pohon #' + id);
    document.getElementById('mon-pohon-id').value = id;
    document.getElementById('mon-user-lat').value = '';
    document.getElementById('mon-user-lng').value = '';
    document.getElementById('mon-user-accuracy').value = '';
    document.getElementById('monitoring-form').reset();
    document.getElementById('mon-pohon-id').value = id; // reset() juga mengosongkan hidden field ini, isi ulang
    document.getElementById('mon-error').style.display = 'none';

    const statusEl = document.getElementById('mon-location-status');
    statusEl.textContent = '📍 Mendapatkan lokasi Anda...';
    statusEl.style.color = '';

    modal.classList.add('open');

    requestMonitoringLocation();
}

function closeMonitoringModal() {
    const modal = document.getElementById('monitoring-modal');
    if (modal) modal.classList.remove('open');
    monitoringCurrentTree = null;
}

function requestMonitoringLocation() {
    const statusEl = document.getElementById('mon-location-status');
    const submitBtn = document.getElementById('mon-submit-btn');

    if (!navigator.geolocation) {
        statusEl.textContent = '⚠️ Perangkat/browser Anda tidak mendukung deteksi lokasi. Monitoring tidak bisa dilakukan dari sini.';
        statusEl.style.color = 'var(--color-danger, #d64545)';
        submitBtn.disabled = true;
        return;
    }

    submitBtn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            const { latitude, longitude, accuracy } = pos.coords;
            document.getElementById('mon-user-lat').value = latitude;
            document.getElementById('mon-user-lng').value = longitude;
            document.getElementById('mon-user-accuracy').value = accuracy;

            let msg = '📍 Lokasi terdeteksi (akurasi ±' + Math.round(accuracy) + ' m).';

            if (monitoringCurrentTree && monitoringCurrentTree.lat && monitoringCurrentTree.lng) {
                const dist = distanceMeters(latitude, longitude, monitoringCurrentTree.lat, monitoringCurrentTree.lng);
                msg += ' Jarak ke pohon: ±' + Math.round(dist) + ' m.';

                const effectiveDistance = Math.max(0, dist - Math.min(accuracy, 500));
                if (effectiveDistance > MONITORING_MAX_DISTANCE_METERS) {
                    statusEl.textContent = msg + ' Ini lebih dari ' + (MONITORING_MAX_DISTANCE_METERS / 1000) + ' km dari pohon — monitoring mungkin ditolak oleh server.';
                    statusEl.style.color = 'var(--color-danger, #d64545)';
                    submitBtn.disabled = false; // biarkan tetap coba submit, server yang jadi keputusan akhir
                    return;
                }

                if (accuracy > 500) {
                    statusEl.textContent = msg + ' Akurasi GPS kurang baik, pastikan GPS aktif jika hasil ditolak.';
                    statusEl.style.color = '#b8860b';
                } else {
                    statusEl.textContent = msg;
                    statusEl.style.color = 'var(--color-primary-dark, #1e7d4d)';
                }
            } else {
                statusEl.textContent = msg;
                statusEl.style.color = 'var(--color-primary-dark, #1e7d4d)';
            }

            submitBtn.disabled = false;
        },
        function (err) {
            let msg = '⚠️ Tidak bisa mendapatkan lokasi Anda.';
            if (err.code === err.PERMISSION_DENIED) {
                msg = '⚠️ Izin lokasi ditolak. Aktifkan izin lokasi di browser untuk melakukan monitoring.';
            } else if (err.code === err.TIMEOUT) {
                msg = '⚠️ Waktu deteksi lokasi habis. Coba lagi.';
            }
            statusEl.textContent = msg;
            statusEl.style.color = 'var(--color-danger, #d64545)';
            submitBtn.disabled = true;
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

async function submitMonitoringForm(event) {
    event.preventDefault();

    const form = document.getElementById('monitoring-form');
    const errorEl = document.getElementById('mon-error');
    const submitBtn = document.getElementById('mon-submit-btn');

    errorEl.style.display = 'none';

    if (!document.getElementById('mon-user-lat').value) {
        errorEl.textContent = 'Lokasi Anda belum terdeteksi. Aktifkan izin lokasi lalu coba lagi.';
        errorEl.style.display = 'block';
        return false;
    }

    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';

    try {
        const formData = new FormData(form);
        const res = await fetch(SITANGKAL_BASE + 'api/monitoring/create.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        });
        const json = await res.json();

        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Gagal menyimpan monitoring');
        }

        closeMonitoringModal();

        if (typeof loadMonitoringHistory === 'function' && monitoringCurrentTree) {
            loadMonitoringHistory(monitoringCurrentTree.id);
        }

        alert('Monitoring berhasil disimpan. Terima kasih!');
    } catch (e) {
        errorEl.textContent = e.message || 'Terjadi kesalahan, silakan coba lagi.';
        errorEl.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }

    return false;
}

// ---------------- Riwayat Monitoring (tampilan publik) ----------------
async function loadMonitoringHistory(pohonId, containerId) {
    containerId = containerId || 'monitoring-history-list';
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '<div class="text-muted" style="font-size:13px;">Memuat riwayat monitoring...</div>';

    try {
        const res = await fetch(
            SITANGKAL_BASE + 'api/monitoring/read.php?pohon_id=' + encodeURIComponent(pohonId),
            {
                credentials: 'same-origin',
            }
        );

        if (res.status === 401) {
            container.innerHTML = '<div class="text-muted" style="font-size:13px;">Login untuk melihat riwayat monitoring pohon ini.</div>';
            return;
        }

        const json = await res.json();

        console.log("Monitoring:", json);

        if (!json.success || !json.data || json.data.length === 0) {
            container.innerHTML = '<div class="text-muted" style="font-size:13px;">Belum ada riwayat monitoring untuk pohon ini.</div>';
            return;
        }

        container.innerHTML = json.data.map(function (m) {

            const badgeClass =
                m.kesehatan_monitoring === 'Sehat'
                    ? 'baik'
                    : (m.kesehatan_monitoring === 'Kurang Sehat'
                        ? 'sedang'
                        : 'buruk');

            const thumbs = (m.media || []).slice(0, 4).map(function (media) {
                if (media.media_type === 'video') {
                    return `
                        <video
                            src="${SITANGKAL_BASE + media.file_path}"
                            style="width:70px;height:70px;object-fit:cover;border-radius:8px;"
                            controls
                        ></video>
                    `;
                }

                return `
                    <img
                        src="${SITANGKAL_BASE + media.file_path}"
                        style="width:70px;height:70px;object-fit:cover;border-radius:8px;"
                    >
                `;
            }).join('');

const detailId = `monitoring-detail-${m.id}`;

return `
<div style="border:1px solid #ddd;border-radius:8px;margin-bottom:10px;overflow:hidden;">

    <div onclick="
        const el=document.getElementById('${detailId}');
        const icon=this.querySelector('.toggle-icon');
        if(el.style.display==='none' || el.style.display===''){
            el.style.display='block';
            icon.innerHTML='&#9650;';
        }else{
            el.style.display='none';
            icon.innerHTML='&#9660;';
        }
    "
    style="
        cursor:pointer;
        display:flex;
        justify-content:space-between;
        align-items:center;
        padding:12px 15px;
        background:#f8f9fa;
    ">

        <div>
            <strong>
                ${new Date(m.tanggal_monitoring).toLocaleDateString('id-ID',{
                    day:'numeric',
                    month:'long',
                    year:'numeric'
                })}
            </strong>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
            <span class="badge ${badgeClass}">
                ${m.kesehatan_monitoring || '-'}
            </span>

            <span class="toggle-icon">&#9660;</span>
        </div>

    </div>

<div id="${detailId}" style="display:none;padding:18px;background:#fff;">

    <div style="display:flex;justify-content:space-between;gap:25px;margin-bottom:15px;">

        <div style="flex:1;">

            <div style="margin-bottom:14px;">
                <div style="font-size:12px;color:#777;">🌳 Tinggi Pohon</div>
                <div style="font-weight:600;font-size:15px;">
                    ${m.tinggi_pohon ?? '-'} m
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <div style="font-size:12px;color:#777;">🌿 Lebar Tajuk</div>
                <div style="font-weight:600;font-size:15px;">
                    ${m.lebar_tajuk ?? '-'} m
                </div>
            </div>

            <div>
                <div style="font-size:12px;color:#777;">⚠️ Jenis Gangguan</div>
                <div style="font-weight:600;font-size:15px;">
                    ${m.jenis_gangguan || '-'}
                </div>
            </div>

        </div>

        <div style="flex:1;">

            <div style="margin-bottom:14px;">
                <div style="font-size:12px;color:#777;">📏 Diameter Batang</div>
                <div style="font-weight:600;font-size:15px;">
                    ${m.diameter_batang ?? '-'} cm
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <div style="font-size:12px;color:#777;">📊 Tingkat Keparahan</div>
                <div style="font-weight:600;font-size:15px;">
                    ${m.tingkat_keparahan || '-'}
                </div>
            </div>

            <div>
                <div style="font-size:12px;color:#777;">✔ Status Tindak Lanjut</div>
                <div style="font-weight:600;font-size:15px;">
                    ${m.status_tindak_lanjut || '-'}
                </div>
            </div>

        </div>

    </div>

    ${
        m.rekomendasi_tindakan
        ? `
        <div style="margin-bottom:15px;">
            <div style="font-size:12px;color:#777;margin-bottom:4px;">
                💡 Rekomendasi
            </div>

            <div style="line-height:1.6;">
                ${escapeHtmlLocal(m.rekomendasi_tindakan)}
            </div>
        </div>
        `
        : ''
    }

    ${
        m.catatan
        ? `
        <div style="margin-bottom:18px;">
            <div style="font-size:12px;color:#777;margin-bottom:4px;">
                📝 Catatan
            </div>

            <div style="line-height:1.7;">
                ${escapeHtmlLocal(m.catatan)}
            </div>
        </div>
        `
        : ''
    }

    ${
        thumbs
        ? `
        <hr style="margin:18px 0;">

        <div style="font-size:13px;font-weight:600;margin-bottom:10px;">
            📷 Dokumentasi Monitoring
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            ${(m.media || []).map(function(media){

                if(media.media_type === 'video'){
                    return `
                        <video
                            src="${SITANGKAL_BASE + media.file_path}"
                            controls
                            style="
                                width:130px;
                                height:95px;
                                object-fit:cover;
                                border-radius:8px;
                                border:1px solid #ddd;
                            ">
                        </video>
                    `;
                }

                return `
                    <img
                        src="${SITANGKAL_BASE + media.file_path}"
                        style="
                            width:130px;
                            height:95px;
                            object-fit:cover;
                            border-radius:8px;
                            border:1px solid #ddd;
                        ">
                `;

            }).join('')}
        </div>
        `
        : ''
    }

</div>

</div>
`;

        }).join('');

    } catch (e) {
        console.error(e);

        container.innerHTML =
            '<div class="text-muted" style="font-size:13px;">Gagal memuat riwayat monitoring.</div>';
    }
}
function escapeHtmlLocal(str) {
    if (typeof escapeHtml === 'function') return escapeHtml(str);
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Tutup modal saat klik area gelap di luar modal-box
document.addEventListener('click', function (e) {
    const modal = document.getElementById('monitoring-modal');
    if (modal && e.target === modal) {
        closeMonitoringModal();
    }
});
