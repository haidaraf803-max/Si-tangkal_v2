<?php
$pageTitle  = 'Peta Sebaran Pohon.';
$activePage = 'map';
require_once __DIR__ . '/../config.php';

// ===== AUTH CHECK (login SELALU di /login.php, di luar folder) =====
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'map', 'view');

require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>
<!-- ======= PAGE HEADING ======= -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Peta Sebaran Pohon</h4>
        <p class="text-muted mb-0" style="font-size:0.8rem;">Kelola semua data pohon Kota Cimahi</p>
    </div>
    <a href="../maps.php" target="_blank" class="btn btn-sm btn-outline-success">
        <i class="bi bi-box-arrow-up-right me-1"></i> Buka di Tab Baru
    </a>
</div>

<div class="card" style="padding:0; overflow:hidden; border:0;">
    <iframe
        id="petaAdminFrame"
        src="../maps.php?embed=1"
        title="Peta sebaran pohon (sama dengan maps.php)"
        style="border:0; width:100%; display:block;"
    ></iframe>
</div>

<script>
(function () {
    var frame = document.getElementById('petaAdminFrame');
    if (!frame) return;

    function resizeFrame() {
        var top = frame.getBoundingClientRect().top;
        // sisakan sedikit jarak bawah biar tidak mepet footer
        var height = window.innerHeight - top - 24;
        if (height < 800) height = 800;
        frame.style.height = height + 'px';
    }

    window.addEventListener('resize', resizeFrame);
    window.addEventListener('load', resizeFrame);
    resizeFrame();
})();
</script>

<?php require_once 'layouts/footer.php'; ?>
