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

## 4. (2026-08-24) Pengajuan Pemangkasan Pohon — hapus langkah Validator, foto wajib, filter

**Alur baru:**
```
Pengajuan (foto WAJIB) -> Disurvey -> perlu dipangkas?
    Ya   -> Tim Tangkas unggah foto sesudah -> Selesai
    Tidak -> Selesai
```
Langkah **"Divalidasi" oleh Validator dihapus sepenuhnya** dari alur.

**File diubah:**
- `Pengajuan.php` — foto pohon sekarang **wajib** (atribut `required` + validasi server; SweetAlert baru jika foto tidak diisi). Sebelumnya opsional.
- `Admin/core/PengajuanModel.php`
  - `submitSurvey()`: hasil "perlu_pemangkasan" sekarang langsung notifikasi **Tim Tangkas** (peran Validator dilewati).
  - Method `validate()` **dihapus**.
  - `eksekusi()`: parameter `$fotoSebelum` dihapus — Tim Tangkas hanya mengunggah **foto sesudah** (foto "sebelum" sudah didapat dari foto wajib saat pengajuan dibuat pemohon).
  - Tambahan `filter()` dan `getDistinctTanggal()` untuk mendukung filter tanggal/status.
- `api/pengajuan/validate.php` — endpoint dinonaktifkan (HTTP 410), karena langkah validasi sudah dihapus.
- `api/pengajuan/eksekusi.php` — tidak lagi menerima `foto_sebelum`.
- `Admin/detail_pengajuan.php` — panel "Validasi Hasil Survey" & variabel `$bolehValidasi` dihapus. Timeline alur disederhanakan jadi Diajukan → Disurvey → Selesai. Panel eksekusi Tim Tangkas kini muncul begitu status "disurvey" + hasil survey "perlu_pemangkasan", dengan 1 field foto (sesudah) yang wajib diisi. Data lama yang masih di status `divalidasi`/`ditangani` otomatis dianggap "disurvey" agar tetap muncul di antrean Tim Tangkas.
- `Admin/edit_pengajuan.php` — **Petugas Survey tidak bisa lagi menambah/mengubah foto apapun**: menu upload foto (sebelum & sesudah) disembunyikan dari UI untuk role `petugas_survey`, dan diblokir juga di sisi server (guard `$isPetugasSurveyOnly`) agar tidak bisa dilewati lewat request manual.
- `Admin/pengajuan.php` — ditambahkan filter **tanggal** (dropdown berisi tanggal yang benar-benar ada, mirip filter Excel) dan **status** (Sudah/Belum), berdampingan dengan pencarian kata kunci yang sudah ada.

**Migrasi database** (lihat `db/migration_pengajuan_bibit_update.sql`):
- `UPDATE pengajuan SET status_tahap='disurvey' WHERE status_tahap IN ('divalidasi','ditangani')` — supaya data yang sempat tersangkut di tahap Validator langsung siap dieksekusi Tim Tangkas.
- Kolom `status_tahap` di tabel `pengajuan` **tidak diubah** (enum lama tetap ada agar tidak perlu ALTER ENUM berisiko); nilai `'divalidasi'`/`'ditangani'` sekadar tidak dipakai lagi oleh aplikasi.

## 5. (2026-08-24) Permohonan Bibit — alur serah terima, filter, notifikasi

**Alur baru:**
```
Permohonan -> Disetujui?
    Ya  -> Disetujui (tanggal_disetujui diisi, notifikasi Tim Pemeliharaan tetap merah)
              -> Serah Terima (foto + tanggal serah terima diisi, notifikasi hilang)
              -> Selesai
    Tidak -> Selesai (Ditolak, tidak ada langkah lanjutan)
```
"Tim Pemeliharaan" dipetakan ke role **Petugas Penanaman** (`role_id = 6`, Pak Ahmad) — role inilah yang sudah memegang hak edit atas modul Permohonan Bibit di RBAC sebelumnya, sehingga dipakai sebagai penerima notifikasi. Jika di lapangan yang dimaksud adalah role lain, ganti konstanta `ROLE_TIM_PEMELIHARAAN_BIBIT` di `Admin/permohonan_bibit.php`.

**File diubah:**
- `Admin/core/NotificationModel.php` — tambahan `notifyRoleBibit()` dan `markReadByBibit()`, memakai kolom baru `notifications.bibit_id` supaya notifikasi bibit terlacak terpisah dari `pengajuan_id`.
- `Admin/permohonan_bibit.php`
  - Aksi **tanggapi**: saat status diubah jadi "Disetujui", `tanggal_disetujui` otomatis diisi `CURDATE()` dan notifikasi (belum dibaca / merah) dikirim ke Tim Pemeliharaan.
  - Aksi baru **serah_terima**: upload `foto_serah_terima` (wajib) + `tanggal_serah_terima` (default hari ini), hanya bisa dijalankan jika status saat ini "Disetujui". Setelah tersimpan, status berubah jadi **"Selesai"** dan notifikasi terkait ditandai sudah dibaca (`markReadByBibit`) sehingga notifikasi merah hilang.
  - Tombol "Serah Terima" (ikon kotak) baru muncul di baris dengan status "Disetujui", membuka modal upload foto + tanggal.
  - Kolom baru di tabel: **Serah Terima** (menampilkan tautan foto + tanggal jika sudah ada, atau "Menunggu serah terima" jika masih di status Disetujui).
  - Filter baru: **tanggal**, **status** (termasuk status baru "Selesai"), dan **jenis tanaman** — semua berupa dropdown berisi nilai yang benar-benar ada di data (mirip filter Excel), bukan input bebas.
- `permohonan_bibit.php` (form publik) — **tidak perlu diubah**: dropdown jenis tanaman sudah mengambil dari `stok_bibit` dan hanya menampilkan jenis dengan `jumlah_tersedia > 0` (item yang stoknya kosong/tidak ada otomatis tidak muncul).

**Migrasi database** (lihat `db/migration_pengajuan_bibit_update.sql`):
- Tabel `permohonan_bibit`: tambah kolom `tanggal_disetujui` (DATE), `foto_serah_terima` (VARCHAR 255), `tanggal_serah_terima` (DATE); ubah enum `status_permohonan` menjadi `('Belum','Disetujui','Ditolak','Selesai')`.
- Tabel `notifications`: tambah kolom `bibit_id` (INT, nullable) + index, dipakai notifikasi alur bibit.

---
**Catatan pengujian:** Lingkungan pengeditan tidak memiliki MySQL untuk menjalankan aplikasi & migrasi secara langsung, tetapi seluruh file `.php` yang diubah sudah lolos `php -l` (syntax check). Alur logika, nama kolom, dan nama tabel sudah dicocokkan manual terhadap skema di `db/dump-db_sitangkal-202608141518.sql`. **Jalankan `db/migration_pengajuan_bibit_update.sql` terlebih dahulu** sebelum meng-deploy file PHP di atas 4 dan 5, lalu tes di environment lokal/staging sebelum dipakai di production.
