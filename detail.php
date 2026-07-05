<?php
/**
 * detail.php
 * Detail publik satu data pengajuan pemangkasan pohon.
 * Sebelumnya file ini memuat './Admin/Pengajuan.php' & class
 * 'Pengajuan' yang TIDAK ADA (selalu fatal error) — sekarang
 * memakai config & model yang sama dengan seluruh aplikasi.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Admin/core/PengajuanModel.php';

$pengajuanModel = new PengajuanModel($config);

if (!isset($_GET['id'])) {
    die('ID tidak ditemukan');
}

$data = $pengajuanModel->getById((int) $_GET['id']);

if (!$data) {
    die('Data tidak ditemukan');
}

$pageTitle = 'Detail Pengajuan - Si-TANGKAL Kota Cimahi';
$activeNav = 'pengajuan';
require_once __DIR__ . '/includes/site-header.php';
?>

<div class="container" style="margin-top:120px; margin-bottom:60px;">
    <h3>Detail Data Pengajuan</h3>

    <table class="table table-bordered">
        <tr>
            <th>No Surat</th>
            <td><?= htmlspecialchars($data['No_Surat'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Nama Pemohon</th>
            <td><?= htmlspecialchars($data['Nama_Pemohon'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Lokasi Pohon</th>
            <td><?= htmlspecialchars($data['Lokasi_Pohon'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Disposisi Surat</th>
            <td><?= htmlspecialchars($data['Disposisi_Surat'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Survey Pohon</th>
            <td><?= htmlspecialchars($data['Survey_Pohon'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Tanggal Penanganan</th>
            <td><?= htmlspecialchars($data['Tanggal_Penanganan'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Keterangan</th>
            <td>
                <?php if (($data['Keterangan'] ?? '') === 'Sudah'): ?>
                    <span class="badge bg-success">Sudah</span>
                <?php else: ?>
                    <span class="badge bg-danger">Belum</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Dokumentasi</th>
            <td>
                <?php if (!empty($data['Dokumentasi'])): ?>
                    <img src="images/<?= htmlspecialchars($data['Dokumentasi']); ?>" width="300" alt="Dokumentasi Pengajuan">
                <?php else: ?>
                    Tidak ada gambar
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <a href="Pengajuan.php" class="btn btn-secondary">Kembali</a>
</div>

<?php require_once __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
