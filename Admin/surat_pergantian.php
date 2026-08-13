<?php
@ob_start();

require_once __DIR__ . '/../config.php';
Auth::requireLogin('../login.php');
require_once 'core/Rbac.php';
Rbac::requireAccess($config, 'pergantian_pohon', 'view');
require_once 'core/PergantianPohonModel.php';

$model = new PergantianModel($config);

if (!isset($_GET['id'])) {
    header('Location: pergantian_pohon.php');
    exit;
}

$data = $model->getById((int) $_GET['id']);
if (!$data) {
    header('Location: pergantian_pohon.php');
    exit;
}

function rupiah($n)
{
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

// Terbilang sederhana (opsional, hanya estetika surat)
function angkaKeTerbilang(int $angka): string
{
    $angka = abs($angka);
    $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    if ($angka < 12) {
        return $huruf[$angka];
    }
    if ($angka < 20) {
        return angkaKeTerbilang($angka - 10) . ' Belas';
    }
    if ($angka < 100) {
        return trim(angkaKeTerbilang((int) ($angka / 10)) . ' Puluh ' . angkaKeTerbilang($angka % 10));
    }
    if ($angka < 200) {
        return 'Seratus ' . angkaKeTerbilang($angka - 100);
    }
    if ($angka < 1000) {
        return trim(angkaKeTerbilang((int) ($angka / 100)) . ' Ratus ' . angkaKeTerbilang($angka % 100));
    }
    if ($angka < 2000) {
        return 'Seribu ' . angkaKeTerbilang($angka - 1000);
    }
    if ($angka < 1000000) {
        return trim(angkaKeTerbilang((int) ($angka / 1000)) . ' Ribu ' . angkaKeTerbilang($angka % 1000));
    }
    if ($angka < 1000000000) {
        return trim(angkaKeTerbilang((int) ($angka / 1000000)) . ' Juta ' . angkaKeTerbilang($angka % 1000000));
    }
    return trim(angkaKeTerbilang((int) ($angka / 1000000000)) . ' Miliar ' . angkaKeTerbilang($angka % 1000000000));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pergantian Pohon - <?= htmlspecialchars($data['nomor_surat'] ?: $data['id']) ?></title>
    <style>
        @page { size: A4; margin: 2.5cm 2cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #111; line-height: 1.5; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop h4, .kop h5 { margin: 0; }
        .kop small { font-size: 10pt; }
        .judul { text-align: center; margin-bottom: 24px; }
        .judul h4 { text-decoration: underline; margin: 0 0 4px; }
        table.data { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.data th, table.data td { border: 1px solid #000; padding: 6px 10px; font-size: 11pt; }
        table.data th { background: #f0f0f0; }
        .ttd { margin-top: 60px; display: flex; justify-content: flex-end; }
        .ttd .kolom { text-align: center; width: 260px; }
        .ttd .nama { margin-top: 70px; font-weight: bold; text-decoration: underline; }
        .no-print { margin-bottom: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 16px; cursor:pointer;">🖨️ Cetak / Simpan sebagai PDF</button>
        <a href="pergantian_pohon.php" style="margin-left:10px;">&larr; Kembali</a>
    </div>

    <div class="kop">
        <h4>PEMERINTAH KOTA CIMAHI</h4>
        <h5>DINAS LINGKUNGAN HIDUP</h5>
        <small>Seksi Konservasi Ruang Terbuka Hijau &mdash; Si-TANGKAL</small>
    </div>

    <div class="judul">
        <h4>SURAT PERHITUNGAN PERGANTIAN POHON</h4>
        Nomor: <?= htmlspecialchars($data['nomor_surat'] ?: '................................') ?>
    </div>

    <p>
        Berdasarkan hasil survey dan perhitungan yang dilakukan pada tanggal
        <strong><?= htmlspecialchars(date('d F Y', strtotime($data['tanggal_surat']))) ?></strong>,
        dengan ini disampaikan hasil perhitungan kewajiban pergantian pohon sebagai berikut:
    </p>

    <table class="data">
        <thead>
            <tr>
                <th>Jenis Pohon</th>
                <th>Diameter</th>
                <th>Jumlah Pohon</th>
                <th>Harga / cm</th>
                <th>Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($data['jenis_pohon']) ?></td>
                <td class="text-center"><?= htmlspecialchars($data['diameter_cm']) ?> cm</td>
                <td class="text-center"><?= (int) $data['jumlah_pohon'] ?> pohon</td>
                <td><?= rupiah($data['harga_per_cm']) ?></td>
                <td><?= rupiah($data['total_biaya']) ?></td>
            </tr>
        </tbody>
    </table>

    <p>
        Total kewajiban pergantian pohon sebesar
        <strong><?= rupiah($data['total_biaya']) ?></strong>
        (<?= htmlspecialchars(angkaKeTerbilang((int) $data['total_biaya'])) ?> Rupiah).
    </p>

    <p>
        Demikian surat perhitungan ini dibuat untuk dipergunakan sebagaimana mestinya.
    </p>

    <div class="ttd">
        <div class="kolom">
            Cimahi, <?= htmlspecialchars(date('d F Y', strtotime($data['tanggal_surat']))) ?><br>
            Kepala Bidang Konservasi
            <div class="nama"><?= htmlspecialchars($data['nama_kabid'] ?: '........................................') ?></div>
            NIP. <?= htmlspecialchars($data['nip_kabid'] ?: '.....................................') ?>
        </div>
    </div>

</body>
</html>
