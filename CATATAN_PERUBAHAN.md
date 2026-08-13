# Catatan Perubahan — Si-TANGKAL

Ringkasan perubahan yang dilakukan sesuai permintaan.

## 1. Hapus informasi bibit di halaman Beranda
**File:** `index.php`
- Menghapus query pengambilan data `stok_bibit`.
- Menghapus seluruh section "Katalog Bibit / Informasi Stok Bibit Tersedia" beserta CSS pendukungnya.

## 2. Pengajuan penebangan/pemangkasan hanya bisa diinput jika sudah login
**File:** `Pengajuan.php`, `login.php`
- Form "Buat Pengajuan Baru" di halaman publik `Pengajuan.php` hanya ditampilkan jika user sudah login (`Auth::isLoggedIn()`). Jika belum login, ditampilkan ajakan untuk login.
- Proses simpan data (backend) juga divalidasi ulang — permintaan POST tanpa login akan ditolak (double protection: UI + backend).
- `login.php` ditambahkan dukungan parameter `?redirect=`, sehingga setelah user login dari halaman Pengajuan, ia diarahkan kembali ke halaman Pengajuan (bukan ke Admin).

## 3. Export CSV (per tanggal / keseluruhan) di semua halaman data Admin
**File baru:**
- `Admin/core/CsvExportHelper.php` — helper untuk membuat & mengirim file CSV.
- `Admin/export.php` — endpoint tunggal untuk semua modul export. Mendukung 3 mode:
  - `mode=all` — seluruh data
  - `mode=tanggal&tanggal=YYYY-MM-DD` — data pada 1 tanggal tertentu
  - `mode=rentang&dari=YYYY-MM-DD&sampai=YYYY-MM-DD` — data pada rentang tanggal
  - Proteksi RBAC: hanya bisa export modul yang memang boleh dilihat user yang login.
- `Admin/layouts/export_modal.php` — komponen tombol "Export CSV" + modal pilihan mode, tinggal di-`require` di halaman mana pun.

**Halaman yang sudah dipasangi tombol Export CSV:**
- `Admin/pohon.php` (khusus "Semua Data" — tabel pohon tidak memiliki kolom tanggal)
- `Admin/pengajuan.php`
- `Admin/monitoring.php`
- `Admin/laporan_penanaman.php`
- `Admin/pemakaian_bbm.php`
- `Admin/pemakaian_pupuk.php`
- `Admin/stok_bibit.php`
- `Admin/permintaan_sarpras.php`
- `Admin/permohonan_bibit.php`
- `Admin/pergantian_pohon.php`
- `Admin/users.php`

### Cara menambah export di halaman baru (jika suatu saat perlu)
```php
<?php
    $exportModul        = 'nama_modul'; // key yang sudah didaftarkan di Admin/export.php
    $exportLabel        = 'Label yang tampil di modal';
    $exportSupportsDate = true; // false jika tabel tidak punya kolom tanggal
    require 'layouts/export_modal.php';
?>
```

---
**Catatan pengujian:** Lingkungan pengeditan tidak memiliki PHP CLI/MySQL untuk menjalankan aplikasi secara langsung, jadi perubahan ini sudah dicek secara manual (keseimbangan tag `<?php`/`?>`, kurung kurawal, dan alur logika) tetapi belum dijalankan di server sungguhan. Disarankan untuk mengetes di environment lokal/staging sebelum dipakai di production.
