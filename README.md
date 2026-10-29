# 🚗 Sistem Peminjaman Mobil Dinas

<p align="center">
  <strong>Sistem Manajemen Penjadwalan, Serah Terima Digital, dan Pemantauan Armada Mobil Dinas Kantor</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13" />
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3+" />
  <img src="https://img.shields.io/badge/React-19-61DAFB?style=for-the-badge&logo=react&logoColor=black" alt="React 19" />
  <img src="https://img.shields.io/badge/TypeScript-Strict-3178C6?style=for-the-badge&logo=typescript&logoColor=white" alt="TypeScript Strict" />
  <img src="https://img.shields.io/badge/Inertia.js-v2-9553E9?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia.js v2" />
  <img src="https://img.shields.io/badge/TailwindCSS-v4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS v4" />
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Pest_Tests-537_Passed-00D084?style=flat-square&logo=pest&logoColor=white" alt="Tests Passed" />
  <img src="https://img.shields.io/badge/PHPStan-Level_7_Passed-blue?style=flat-square" alt="PHPStan Level 7" />
  <img src="https://img.shields.io/badge/Oxlint-Clean-success?style=flat-square" alt="Oxlint Clean" />
  <img src="https://img.shields.io/badge/Security-OWASP_Top_10_Audited-green?style=flat-square" alt="Security Audited" />
  <img src="https://img.shields.io/badge/License-Proprietary-red?style=flat-square" alt="License" />
</p>

---

## 📖 Daftar Isi

- [Tentang Aplikasi](#-tentang-aplikasi)
- [Fitur Utama](#-fitur-utama)
- [Teknologi & Arsitektur](#-teknologi--arsitektur)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Panduan Instalasi Cepat (Development)](#-panduan-instalasi-cepat-development)
- [Panduan Operasional & Komisioning PC Kantor (Laragon)](#-panduan-operasional--komisioning-pc-kantor-laragon)
    - [1. Konfigurasi Domain Lokal & SSL (Akses Kamera/Webcam)](#1-konfigurasi-domain-lokal--ssl-akses-kamerawebcam)
    - [2. Pembuatan User MySQL Khusus (Non-Root)](#2-pembuatan-user-mysql-khusus-non-root)
    - [3. Penjadwal Otomatis (Windows Task Scheduler)](#3-penjadwal-otomatis-windows-task-scheduler)
    - [4. Konfigurasi Autostart Laragon](#4-konfigurasi-autostart-laragon)
- [Sistem Cadangan & Pemulihan (Backup & Restore)](#-sistem-cadangan--pemulihan-backup--restore)
    - [Struktur & Lokasi Penyimpanan](#struktur--lokasi-penyimpanan)
    - [Perintah Cadangan Manual](#perintah-cadangan-manual)
    - [Prosedur Pemulihan Data (Restore Drill)](#prosedur-pemulihan-data-restore-drill)
- [Perintah Perawatan Akun Pengelola](#-perintah-perawatan-akun-pengelola)
    - [Pembuatan Akun Pengelola Darurat](#pembuatan-akun-pengelola-darurat)
    - [Reset Password Pengelola Terkunci](#reset-password-pengelola-terkunci)
- [Pengelolaan Master Divisi](#-pengelolaan-master-divisi)
- [Pengujian & Standar Mutu Kode](#-pengujian--standar-mutu-kode)
- [Panduan Pengguna (Petugas GA)](#-panduan-pengguna-petugas-ga)
- [Panduan Migrasi ke Server Produksi](#-panduan-migrasi-ke-server-produksi)
- [Troubleshooting & Tanya Jawab](#-troubleshooting--tanya-jawab)

---

## 🎯 Tentang Aplikasi

**Sistem Peminjaman Mobil Dinas** adalah platform internal yang dirancang khusus untuk mempermudah operasional unit **General Affairs (GA)** dalam mencatat jadwal, memantau penggunaan armada, memverifikasi serah terima kendaraan secara digital, dan mencegah tumpang tindih (_double booking_).

Aplikasi ini beroperasi dengan model **petugas-sentris**:

- Hanya petugas GA terdaftar (`manager` / "Pengelola" dan `admin` / "Admin") yang dapat masuk ke dalam sistem.
- Pegawai kantor peminjam bertindak sebagai master data terverifikasi (tidak memerlukan kredensial login akun).
- Alur inti operasional transparan: **Dijadwalkan ➔ Aktif (Serah Terima Ambil + 1 Foto) ➔ Selesai (Serah Terima Kembali + 1 Foto)**.

---

## ✨ Fitur Utama

- 🚗 **Manajemen Armada Mobil:** Pendataan unit lengkap dengan plat nomor baku standar Indonesia, status operasional (_Operasional_ vs _Maintenance_), kapasitas, dan slot foto referensi fisik unit.
- 📅 **Penjadwalan Cerdas & Anti-Bentrok:**
    - Validasi bentrok jadwal armada secara presisi berbasis transaksi database (`lockForUpdate`).
    - Aturan _satu pegawai, satu mobil pada satu waktu_ untuk mencegah penjadwalan ganda atas orang yang sama.
    - Kalender dinamis kustom bahasa Indonesia (pemilihan tanggal, jam, menit step, serta penanda akhir pekan).
- 📸 **Serah Terima Digital Terverifikasi:**
    - Pengambilan foto langsung dari kamera (_webcam_ PC / laptop) atau unggah berkas bukti.
    - Kompresi gambar otomatis di sisi peramban (_client-side compression_) sebelum diunggah untuk menghemat ruang penyimpanan.
    - Penyimpanan foto privat terlindungi di balik kebijakan hak akses (_Policy Gate_).
- ⏱️ **Indikator Turunan Dinamis:**
    - Deteksi otomatis status _Belum Diambil_ (jadwal lewat waktu mulai tetapi belum serah terima ambil).
    - Deteksi otomatis status _Terlambat_ (mobil aktif melewati rencana jam kembali).
    - Deteksi otomatis _Selesai Terlambat_ tanpa manipulasi data tersimpan.
- 🔄 **Perpanjangan Durasi & Pembatalan:**
    - Fasilitas perpanjangan waktu pengembalian dengan pengecekan ulang bentrok jadwal mendatang.
    - Pembatalan jadwal disertai kewajiban pencatatan alasan yang valid.
- 📊 **Laporan & Ekspor Data Berperiode:**
    - Penyaringan data riwayat peminjaman berdasarkan status, mobil, pegawai, dan rentang tanggal (maks. 366 hari).
    - Ekspor instan ke format **Excel (.xlsx)** dan **CSV (.csv)** dengan format tanggal lokal Indonesia.
- 🔒 **Keamanan Ketat & Audit Trail:**
    - Otentikasi dua langkah (2FA TOTP Google Authenticator/Authy).
    - Kebijakan password kuat dengan penanganan akun sementara (_must change password_).
    - Seluruh manipulasi data tercatat rinci di log aktivitas sistem (_who, what, when, old vs new values_).
- 💾 **Pencadangan Otomatis:** Cadangan database dan foto otomatis setiap hari pukul 01:30 dengan retensi 30 hari.

---

## 🛠️ Teknologi & Arsitektur

Aplikasi dibangun sebagai **Monolith Modular** menggunakan kombinasi teknologi modern:

| Komponen               | Spesifikasi / Teknologi                                                        |
| :--------------------- | :----------------------------------------------------------------------------- |
| **Backend**            | Laravel 13 (PHP 8.3+)                                                          |
| **Frontend**           | React 19, TypeScript (Strict Mode), Inertia.js v2                              |
| **Routing Frontend**   | Laravel Wayfinder (Fungsi rute bertipe aman)                                   |
| **Desain & UI**        | shadcn/ui (Preset Nova, Neutral, Blue), Tailwind CSS v4, Lucide Icons          |
| **Tipografi**          | Font Inter lokal (`@fontsource-variable/inter`) — _100% bebas CDN eksternal_   |
| **Otentikasi & 2FA**   | Laravel Fortify (Login, Two-Factor Authentication, Rate Limiting)              |
| **Basis Data**         | MySQL 8.0+                                                                     |
| **Penyimpanan Berkas** | Laravel Filesystem (`local` privat untuk foto bukti, `public` untuk foto unit) |
| **Kualitas Kode**      | Pest PHP, Larastan (PHPStan Level 7), Laravel Pint, Oxlint, Oxfmt              |

---

## 📋 Persyaratan Sistem

### Lingkungan Standar (PC Kantor):

- **Sistem Operasi:** Windows 10 / 11 (64-bit)
- **Paket Web Server:** Laragon Full (disarankan PHP 8.3+ dan MySQL 8.0+)
- **Node.js:** Versi 20 LTS atau yang lebih baru
- **Composer:** Versi 2.x
- **Peramban:** Google Chrome / Microsoft Edge versi terbaru (mendukung Web API `getUserMedia` untuk kamera)

---

## 🚀 Panduan Instalasi Cepat (Development)

Bagi pengembang yang ingin menjalankan proyek di lingkungan pengembangan lokal:

```bash
# 1. Kloning repositori
git clone https://github.com/NajibAnanda/sistem-peminjaman-mobil.git
cd sistem-peminjaman-mobil

# 2. Pasang dependensi PHP dan Node.js
composer install
npm install

# 3. Siapkan file konfigurasi lingkungan
cp .env.example .env
php artisan key:generate

# 4. Konfigurasikan koneksi MySQL di file .env, lalu jalankan migrasi & seeder
php artisan migrate:fresh --seed

# 5. Bangun aset frontend & jalankan server lokal
composer run dev
```

Aplikasi dapat diakses melalui peramban di: `http://localhost:8000`

---

## 🏢 Panduan Operasional & Komisioning PC Kantor (Laragon)

Ikuti panduan berikut saat memasang dan mengonfigurasi aplikasi untuk operasional harian kantor:

### 1. Konfigurasi Domain Lokal & SSL (Akses Kamera/Webcam)

> [!IMPORTANT]
> Akses kamera (_webcam_) pada peramban web modern mewajibkan protokol aman **HTTPS** (`getUserMedia` security requirement). Selain itu, nama folder tidak boleh menggunakan karakter garis bawah (`_`) karena melanggar standar RFC hostname.

1. Tutup editor kode dan matikan Laragon (**Stop All**).
2. Ubah nama folder instalasi di `C:\laragon\www\`:
    ```text
    C:\laragon\www\sistem_peminjaman_mobil  ──>  C:\laragon\www\sistem-peminjaman-mobil
    ```
3. Buka Laragon:
    - Klik kanan > **Apache** > **SSL** > pastikan centang **Enabled**.
    - Klik **Start All**. Laragon akan otomatis membuat sertifikat SSL lokal untuk virtual host.
4. Buka file `.env` di folder aplikasi, sesuaikan:
    ```env
    APP_URL=https://sistem-peminjaman-mobil.test
    ```
5. Buka `https://sistem-peminjaman-mobil.test` di browser. Izinkan peramban saat meminta akses kamera.

---

### 2. Pembuatan User MySQL Khusus (Non-Root)

Untuk mencegah risiko kebocoran hak akses database di lingkungan kantor, hindari penggunaan akun `root`:

1. Buka HeidiSQL atau terminal MySQL di Laragon.
2. Eksekusi perintah SQL berikut untuk membuat pengguna database khusus dengan hak akses terbatas:
    ```sql
    CREATE USER 'peminjaman_app'@'localhost' IDENTIFIED BY 'PasswordAmanKantor2026!';
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES, SHOW VIEW, TRIGGER
    ON peminjaman_mobil.* TO 'peminjaman_app'@'localhost';
    FLUSH PRIVILEGES;
    ```
3. Perbarui konfigurasi pada file `.env`:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=peminjaman_mobil
    DB_USERNAME=peminjaman_app
    DB_PASSWORD=PasswordAmanKantor2026!
    ```

---

### 3. Penjadwal Otomatis (Windows Task Scheduler)

Sistem membutuhkan _heartbeat_ rutin setiap menit untuk mengeksekusi rotasi cadangan dan antrean tugas:

Tersedia skrip [scheduler.bat](file:///c:/laragon/www/sistem_peminjaman_mobil/scheduler.bat) di direktori utama proyek. Anda dapat mendaftarkannya dengan dua cara:

#### Cara A: Melalui Perintah Administrator (Rekomendasi Cepat)

Buka **Command Prompt** (Run as Administrator), lalu jalankan:

```cmd
schtasks /create /tn "SistemPeminjamanMobil_Scheduler" /tr "C:\laragon\www\sistem-peminjaman-mobil\scheduler.bat" /sc minute /mo 1 /ru SYSTEM
```

#### Cara B: Melalui Antarmuka Visual (Task Scheduler GUI)

1. Tekan tombol `Win + R`, ketik `taskschd.msc`, tekan **Enter**.
2. Di panel kanan, klik **Create Task...**
3. **Tab General:**
    - Nama: `Sistem Peminjaman Mobil - Scheduler`
    - Opsi Keamanan: Pilih _Run whether user is logged on or not_ dan centang _Run with highest privileges_.
4. **Tab Triggers:**
    - Klik **New...**
    - Mulai tugas: _At startup_ (atau _Daily_ jam 00:00).
    - Pengaturan lanjutan: Centang **Repeat task every: 1 minute** dengan durasi **Indefinitely**.
5. **Tab Actions:**
    - Klik **New...**
    - Program/script: `C:\laragon\www\sistem-peminjaman-mobil\scheduler.bat`
    - Start in (opsional): `C:\laragon\www\sistem-peminjaman-mobil`
6. Klik **OK** dan masukkan kredensial administrator Windows jika diminta.

_Log eksekusi penjadwal tersimpan harian di: `storage\logs\scheduler-YYYYMMDD.log`._

---

### 4. Konfigurasi Autostart Laragon

Agar sistem langsung aktif saat PC kantor dinyalakan:

1. Buka jendela utama Laragon, klik ikon gerigi (**Preferences**).
2. Pada tab **General**, aktifkan pilihan:
    - `[x] Run Laragon when Windows starts`
    - `[x] Start All automatically`

---

## 💾 Sistem Cadangan & Pemulihan (Backup & Restore)

Sistem menggunakan pustaka `spatie/laravel-backup` yang telah disesuaikan dengan arsitektur GA.

### Struktur & Lokasi Penyimpanan

- **Database:** Dump terkompresi dari database `peminjaman_mobil`.
- **Foto Privat (Bukti Serah Terima):** `storage/app/private/bookings/`
- **Foto Publik (Katalog Mobil):** `storage/app/public/cars/`
- **Folder Arsip Cadangan (.zip):** `storage/app/backups/Peminjaman Mobil/`
  _(Dapat diarahkan ke drive eksternal melalui variabel `BACKUP_DISK_PATH="D:/backups_kantor"` pada file `.env`)._

### Perintah Cadangan Manual

```bash
# Menjalankan proses backup data & berkas secara manual
php artisan backup:run --disable-notifications

# Memeriksa status kesehatan & daftar riwayat arsip cadangan
php artisan backup:list

# Membersihkan arsip cadangan yang telah melewati masa retensi (30 hari)
php artisan backup:clean --disable-notifications
```

### Prosedur Pemulihan Data (Restore Drill)

Prosedur pemulihan telah melalui uji coba nyata (_verified restore drill_) dan berhasil memulihkan 100% data transaksi dan berkas foto:

1. Buka folder arsip cadangan, pilih file `.zip` terbaru (contoh: `2026-10-28-03-34-19.zip`).
2. Ekstrak file SQL dump dari dalam arsip: `db-dumps/mysql-peminjaman_mobil.sql`.
3. Impor basis data melalui Command Prompt:
    ```cmd
    "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u peminjaman_app -p peminjaman_mobil < mysql-peminjaman_mobil.sql
    ```
4. Salin kembali folder berkas foto dari arsip `.zip` ke sistem:
    - Ekstrak isi folder `private/bookings/` ➔ masukkan ke `storage/app/private/bookings/`
    - Ekstrak isi folder `public/cars/` ➔ masukkan ke `storage/app/public/cars/`
5. Aplikasi siap beroperasi kembali dengan data yang pulih sempurna.

---

## 🔐 Perintah Perawatan Akun Pengelola

Aplikasi sengaja **tidak menyediakan fitur registrasi publik atau tautan reset password via email** untuk mencegah celah eksploitasi. Seluruh pengelolaan akun darurat dilakukan melalui CLI Artisan:

### Pembuatan Akun Pengelola Darurat

Gunakan perintah ini apabila PC kantor kehilangan seluruh akun Pengelola aktif atau saat komisioning pertama kali:

```bash
php artisan fleet:create-emergency-manager
```

Opsi parameter non-interaktif:

```bash
php artisan fleet:create-emergency-manager --name="Kepala Bagian GA" --email="kabag.ga@kantor.test" --nip="198501012010011001" --phone="081234567890" --force
```

Sistem akan meminta konfirmasi, mencatat aktivitas ke audit log, dan menerbitkan **password sementara** (16 karakter acak). Petugas wajib mengganti password saat login pertama.

### Reset Password Pengelola Terkunci

Jika petugas dengan hak akses Pengelola lupa kata sandi:

```bash
php artisan fleet:reset-manager-password email_pengelola@kantor.test
```

Sistem akan mereset password akun menjadi password sementara baru dan otomatis mengakhiri seluruh sesi login lama yang sedang aktif.

---

## 🏢 Pengelolaan Master Divisi

Daftar divisi kantor dapat dikonfigurasi melalui dua cara:

1. **Melalui Antarmuka Aplikasi:** Masuk ke menu **Pegawai** ➔ **Tambah/Edit Pegawai**, ketik nama divisi baru pada form untuk mendaftarkannya secara instan.
2. **Melalui File Konfigurasi:** Sesuaikan daftar divisi default pada [config/fleet.php](file:///c:/laragon/www/sistem_peminjaman_mobil/config/fleet.php) pada array `'divisions'`.

---

## 🧪 Pengujian & Standar Mutu Kode

Seluruh kode aplikasi wajib mematuhi standar mutu tinggi tanpa toleransi error:

```bash
# Menjalankan seluruh pengujian fitur dan unit (Pest PHP)
php artisan test

# Menjalankan rangkaian uji mutu lengkap (Pint + Larastan + Pest)
composer test

# Format standar kode PHP (Laravel Pint)
vendor/bin/pint

# Analisis statis PHP level tinggi (Larastan Level 7)
vendor/bin/phpstan analyse

# Linter dan formatter frontend (Oxlint + Oxfmt)
npm run check

# Memperbaiki format kode frontend secara otomatis
npm run check:fix

# Pengecekan tipe ketat TypeScript
npm run types:check

# Kompilasi bundel produksi frontend
npm run build
```

---

## 📚 Panduan Pengguna (Petugas GA)

Panduan operasional alur harian yang ditujukan khusus bagi petugas General Affairs tanpa bahasa teknis tersedia pada dokumen:
👉 [**docs/panduan-pengguna.md**](file:///c:/laragon/www/sistem_peminjaman_mobil/docs/panduan-pengguna.md)

Panduan tersebut memuat:

- Cara membuat jadwal peminjaman baru.
- Tata cara serah terima ambil (pengambilan foto bukti).
- Tata cara serah terima kembali (pengambilan foto selesai).
- Tata cara perpanjangan durasi peminjaman dan pembatalan jadwal.
- Cara melakukan filter riwayat dan mengunduh laporan Excel/CSV.

---

## 🌐 Panduan Migrasi ke Server Produksi

Jika sistem dipindahkan dari PC lokal kantor ke server terpusat (Linux Ubuntu / Docker / Cloud VPS):

### 1. Checklist Konfigurasi `.env` Server:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://peminjaman-mobil.instansi.go.id
APP_TIMEZONE=Asia/Jakarta
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning

# Wajibkan 2FA untuk seluruh akun Pengelola di lingkungan internet
FLEET_REQUIRE_2FA_FOR_MANAGER=true

# Aktifkan pengecekan kebocoran password (pwned passwords API)
FLEET_PASSWORD_CHECK_UNCOMPROMISED=true
```

### 2. Optimasi Cache Produksi:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan optimize
php artisan view:cache
php artisan event:cache
```

---

## ❓ Troubleshooting & Tanya Jawab

<details>
<summary><strong>1. Kamera (Webcam) tidak dapat diakses saat serah terima ambil/kembali</strong></summary>

- **Penyebab:** Peramban memblokir akses kamera jika halaman tidak diakses melalui protokol aman (HTTPS).
- **Solusi:** Pastikan URL pada bilah alamat peramban dimulai dengan `https://` (misal: `https://sistem-peminjaman-mobil.test`). Periksa izin kamera pada pengaturan peramban (_Site Settings > Permissions > Camera: Allow_).

</details>

<details>
<summary><strong>2. Eksekusi backup gagal dengan pesan error `mysqldump: command not found`</strong></summary>

- **Penyebab:** Path aplikasi `mysqldump.exe` pada MySQL Laragon belum didefinisikan.
- **Solusi:** Tambahkan path folder binary MySQL Laragon pada file `.env`:
    ```env
    DB_DUMP_PATH="C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin"
    ```
    _(Sesuaikan versi folder MySQL yang terpasang di komputer)._

</details>

<details>
<summary><strong>3. Muncul halaman `419 | Sesi Telah Berakhir`</strong></summary>

- **Penyebab:** Token keamanan CSRF telah kedaluwarsa karena halaman didiamkan terlalu lama tanpa aktivitas.
- **Solusi:** Cukup klik tombol **Muat Ulang Halaman** atau masuk kembali (_login_) untuk melanjutkan aktivitas.

</details>

<details>
<summary><strong>4. Foto bukti serah terima tidak tampil di detail peminjaman</strong></summary>

- **Penyebab:** Foto bukti disimpan di media privat dan dilindungi otorisasi gate.
- **Solusi:** Pastikan pengguna telah login dengan akun berhak akses (`admin` atau `manager`). Foto bukti privat disajikan secara aman melalui rute terenkripsi dan tidak disajikan secara publik.

</details>

---

<p align="center">
  Dikembangkan untuk Efisiensi & Transparansi Operasional Bagian Umum (General Affairs).<br />
  Hak Cipta &copy; 2026 Sistem Peminjaman Mobil Dinas.
</p>
