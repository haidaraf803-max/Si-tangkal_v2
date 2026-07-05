<?php
/**
 * includes/site-footer.php
 * ---------------------------------------------------------
 * Footer YANG SAMA untuk semua halaman publik gaya "landing".
 * Pasangan dari includes/site-header.php.
 *
 * Halaman boleh menambahkan script khusus (mis. SweetAlert
 * notifikasi form) SETELAH require file ini, sebelum </body>.
 * ---------------------------------------------------------
 */
?>
  <!-- ======= FOOTER ======= -->
  <footer id="footer">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-6 col-md-12">
          <h4>Si-TANGKAL Kota Cimahi</h4>
          <p>
            Dinas Lingkungan Hidup Gd. C Lt. 4<br>
            Komplek Perkantoran Pemkot Cimahi<br>
            Jl. Rd. Demang Hardjakusumah, Cibabat, Cimahi<br><br>
            <strong>Telepon:</strong> (022) 6632614<br>
            <strong>Website:</strong> dlh.cimahikota.go.id
          </p>
        </div>
        <div class="col-lg-6 col-md-12 footer-links">
          <h4>Link Terkait</h4>
          <ul>
            <li><i class="bi bi-chevron-right me-2 text-success"></i> <a href="https://www.menlhk.go.id" target="_blank">Kementerian Lingkungan Hidup dan Kehutanan</a></li>
            <li><i class="bi bi-chevron-right me-2 text-success"></i> <a href="https://dlh.cimahikota.go.id" target="_blank">Dinas Lingkungan Hidup Kota Cimahi</a></li>
            <li><i class="bi bi-chevron-right me-2 text-success"></i> <a href="https://www.cimahikota.go.id" target="_blank">Portal Resmi Kota Cimahi</a></li>
          </ul>
        </div>
      </div>
      <div class="copyright">
        &copy; <?= date('Y') ?> <strong><span>Si-TANGKAL</span></strong>. Dinas Lingkungan Hidup Kota Cimahi.
      </div>
    </div>
  </footer>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // Mobile Nav Toggle
    document.querySelector('.mobile-nav-toggle').addEventListener('click', function () {
      const navbar = document.querySelector('#navbar ul');
      if (navbar.style.display === 'flex') {
        navbar.style.display = 'none';
      } else {
        navbar.style.display = 'flex';
        navbar.style.flexDirection = 'column';
        navbar.style.position = 'absolute';
        navbar.style.top = '100%';
        navbar.style.left = '0';
        navbar.style.right = '0';
        navbar.style.background = 'rgba(255,255,255,0.98)';
        navbar.style.padding = '15px 0';
        navbar.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
      }
    });

    // Header scroll effect
    window.addEventListener('scroll', () => {
      const header = document.getElementById('header');
      if (window.scrollY > 50) {
        header.classList.add('header-scrolled');
      } else {
        header.classList.remove('header-scrolled');
      }
    });
  </script>
<?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
  <script src="<?= htmlspecialchars($js) ?>"></script>
<?php endforeach; endif; ?>
