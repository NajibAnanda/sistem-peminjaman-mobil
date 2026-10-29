# PROMPTS.md — Panduan Prompt Agent

Kumpulan prompt untuk membangun **Sistem Peminjaman Mobil Dinas** dari awal sampai siap dipakai, mengikuti `prd.md` dan `AGENTS.md`.

---

## Cara Memakai

1. Selesaikan **Persiapan Manual** di bawah sebelum memberi prompt apa pun ke agent.
2. Jalankan prompt **berurutan**, satu fase per sesi. Jangan menggabungkan beberapa fase dalam satu prompt.
3. Setelah agent melapor selesai:
    - Cek **Checklist Verifikasi** fase tersebut sendiri (buka aplikasi, coba fiturnya).
    - Kalau ada yang salah, gunakan **Prompt Perbaikan** di bagian akhir.
    - Kalau sudah benar, commit lalu lanjut ke fase berikutnya.
4. Kalau sesi agent terputus atau konteksnya penuh, mulai sesi baru dengan **Prompt Melanjutkan**.
5. Kalau agent menanyakan sesuatu yang tidak ada di PRD, jawab dulu. Jangan biarkan agent menebak.

---

## Persiapan

> **Status project ini (16 Okt 2026):** persiapan dan Prompt 0 sudah selesai. Folder project bernama `sistem_peminjaman_mobil` (bukan `peminjaman-mobil`) dan akan di-rename menjadi `sistem-peminjaman-mobil` di Fase 8 untuk SSL Laragon. Test memakai database MySQL terpisah `peminjaman_mobil_test`. Keputusan hasil Prompt 0 sudah dimasukkan ke `prd.md` v2.1, `AGENTS.md`, dan prompt fase di bawah.

Pilih **salah satu**: kerjakan sendiri (Opsi A) atau minta agent mengerjakannya (Opsi B). Bagian **Wajib Dikerjakan Sendiri** tetap harus kamu lakukan di kedua opsi.

### Wajib Dikerjakan Sendiri

- [ ] Laragon terpasang dan berjalan (MySQL menyala), dengan versi PHP yang memenuhi syarat Laravel 13, Composer, Node.js LTS, Git, dan Laravel Installer (`composer global require laravel/installer`).
- [ ] Identitas Git sudah diatur (sekali saja per komputer):
      `git config --global user.name "Nama Kamu"` dan `git config --global user.email "email@kamu.com"`
- [ ] Buat folder `C:\laragon\www\dokumen-peminjaman\` dan simpan `prd.md`, `AGENTS.md`, dan `PROMPTS.md` di sana.
- [ ] Setelah persiapan selesai: jalankan `composer run dev` di folder project, buka aplikasinya di browser, cek halaman login, dashboard, dan settings di **light dan dark mode**. Tekan `Ctrl + C` untuk menghentikan.

### Opsi A — Dikerjakan Sendiri

- [ ] Di `C:\laragon\www`, buat project: `laravel new peminjaman-mobil` → pilih starter kit **React**, autentikasi **bawaan Laravel** (bukan WorkOS), testing **Pest**, database **MySQL**. Jika ditawari Laravel Boost, pilih **tidak** untuk sekarang.
- [ ] Masuk ke folder project, lalu commit awal:
      `git init`, `git add .`, `git commit -m "chore: init laravel react starter kit"`
- [ ] Terapkan preset shadcn: `npx shadcn@latest apply --preset b1PzeK`, cek tampilan, lalu commit:
      `git add .`, `git commit -m "chore: apply shadcn preset nova blue"`
- [ ] Buat database kosong `peminjaman_mobil` (collation `utf8mb4_unicode_ci`) di HeidiSQL.
- [ ] Isi `.env`: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=peminjaman_mobil`, `DB_USERNAME=root`, `DB_PASSWORD=` (sementara untuk development), lalu jalankan `php artisan migrate`.
- [ ] Salin `prd.md`, `AGENTS.md`, dan `PROMPTS.md` ke root project.
- [ ] Buat `CLAUDE.md` di root yang isinya satu baris: `@AGENTS.md`.
- [ ] Commit: `git add .`, `git commit -m "docs: add prd and agent guidelines"`

### Opsi B — Dikerjakan Agent (Prompt Setup)

Buka agent di folder `C:\laragon\www` (bukan di dalam folder project, karena project belum ada), lalu berikan prompt berikut. Setelah agent melapor selesai, lakukan pengecekan tampilan di bagian **Wajib Dikerjakan Sendiri**, lalu buka sesi agent baru **di dalam folder project** untuk Prompt 0.

```
Tugasmu adalah MENYIAPKAN project, bukan membangun fitur. Jangan menulis kode
aplikasi apa pun. Lingkungan: Windows + Laragon. Kerjakan berurutan dan
berhenti bila ada langkah yang gagal.

1. Cek prasyarat dan laporkan versinya: php -v, composer -V, laravel --version,
   node -v, npm -v, git --version, mysql --version, dan koneksi MySQL
   (mysql -u root -e "SELECT VERSION();"). Pastikan versi PHP memenuhi syarat
   Laravel 13. Jika ada yang kurang, BERHENTI dan beri tahu saya cara
   memenuhinya.

2. Di folder saat ini (C:\laragon\www), buat project baru bernama
   peminjaman-mobil dengan Laravel Installer: starter kit React, autentikasi
   bawaan Laravel (bukan WorkOS), testing Pest, database MySQL, inisialisasi
   Git. Gunakan opsi non-interaktif; cek `laravel new --help` untuk nama opsi
   yang berlaku. JANGAN memasang Laravel Boost. Setelah ini, semua langkah
   dikerjakan di dalam folder peminjaman-mobil.

3. Pastikan ada commit awal: "chore: init laravel react starter kit".

4. Database:
   - Buat database jika belum ada:
     CREATE DATABASE IF NOT EXISTS peminjaman_mobil
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   - Isi .env: APP_NAME="Peminjaman Mobil", DB_CONNECTION=mysql,
     DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=peminjaman_mobil,
     DB_USERNAME=root, DB_PASSWORD= (kosong). Pemakaian root ini SENGAJA,
     sementara untuk development lokal; jangan diubah.
   - Jalankan php artisan migrate dan pastikan berhasil.

5. Preset shadcn: jalankan `npx shadcn@latest apply --preset b1PzeK` dan
   setujui penimpaan file yang diminta. Lalu jalankan npm run build dan cek
   TypeScript/lint dengan script yang tersedia di package.json. Jika ada error
   akibat preset, perbaiki seminimal mungkin dan laporkan apa yang diubah.
   Commit: "chore: apply shadcn preset nova blue" (gunakan git add . agar
   file baru ikut).

6. Dokumen: salin prd.md, AGENTS.md, dan PROMPTS.md dari
   C:\laragon\www\dokumen-peminjaman\ ke root project tanpa mengubah isinya.
   Buat CLAUDE.md di root berisi satu baris: @AGENTS.md
   Pastikan .env tidak ikut ter-commit. Commit:
   "docs: add prd and agent guidelines".

7. Jalankan php artisan test untuk memastikan test bawaan starter kit lolos.

Laporkan: versi semua tools, versi Laravel/Inertia/React/Tailwind yang
terpasang, opsi installer yang dipakai, isi git log --oneline, hasil migrate,
build, dan test, serta hal yang perlu saya cek manual.
JANGAN lanjut ke Prompt 0.
```

---

## Prompt 0 — Orientasi & Rencana

```
Baca prd.md dan AGENTS.md secara menyeluruh, lalu periksa struktur project ini
(starter kit Laravel 13 + React yang sudah diberi preset shadcn Nova + Blue).

JANGAN menulis atau mengubah kode apa pun di sesi ini.

Berikan laporan:
1. Ringkasan pemahamanmu tentang sistem (maks. 10 poin).
2. Kondisi starter kit saat ini: versi Laravel/Inertia/React, fitur auth yang
   aktif (termasuk registrasi), script di composer.json & package.json, dan
   komponen shadcn yang sudah ada.
3. Perbedaan antara kondisi starter kit dan kebutuhan PRD yang harus
   disesuaikan di Fase 1.
4. Rencana kerja per fase (Fase 1–8) sesuai urutan di bagian bawah prompt ini,
   termasuk paket yang akan dipasang di tiap fase.
5. Hal yang ambigu atau bertentangan di prd.md/AGENTS.md yang perlu keputusan
   saya.

Catatan: DB sementara memakai user root Laragon untuk development lokal. Ini
disengaja dan akan diganti sebelum produksi; jangan ubah kredensial .env.

Urutan fase:
1 Fondasi, 2 Akun Petugas, 3 Data Master, 4 Inti Peminjaman,
5 Monitoring, 6 Riwayat & Laporan, 7 Audit Kualitas & Keamanan,
8 Operasional & Serah Terima.
```

---

## Prompt 1 — Fondasi

```
Kerjakan FASE 1 — FONDASI sesuai prd.md (section 6, 10, 11, 13, 16) dan AGENTS.md.
Kerjakan HANYA fase ini.

Cakupan:
1. Tooling kualitas (pakai bawaan starter kit; JANGAN pasang ESLint/Prettier):
   - Pastikan Pint, Larastan (level 7, minimal 6), Vite+ (npm run check:
     oxlint + oxfmt), dan TypeScript strict (npm run types:check) berjalan.
   - Perbarui workflow GitHub Actions (.github/workflows/tests.yml): service
     MySQL 8 untuk DB test peminjaman_mobil_test, lalu jalankan Pint (test
     mode), Larastan, Pest, npm run check, dan npm run types:check.
   - Perbaiki .env.example (APP_NAME, DB_DATABASE=peminjaman_mobil,
     APP_URL=http://localhost:8000, APP_TIMEZONE=Asia/Jakarta, APP_LOCALE=id)
     dan beri komentar pada variabel yang ditambahkan.
2. Konfigurasi aplikasi:
   - Zona waktu dibaca dari APP_TIMEZONE (default Asia/Jakarta) dan dipakai
     konsisten; locale id, faker id_ID; format tanggal Indonesia via satu
     helper frontend terpusat.
   - lang/id/ ditulis manual (validation, auth, passwords, pagination),
     termasuk nama atribut field dalam Bahasa Indonesia. Tanpa paket.
   - config/fleet.php: daftar divisi (Keuangan, SDM, Teknik), batas ukuran
     foto (5 MB), dimensi maks foto (1600 px), interval polling (60 detik),
     toleransi waktu mulai (15 menit), FLEET_REQUIRE_2FA_FOR_MANAGER
     (default false), FLEET_PASSWORD_CHECK_UNCOMPROMISED (default false).
     Tambahkan kedua variabel FLEET_* ke .env.example dengan komentar.
   - Model::preventLazyLoading() dan strict mode di non-production.
   - Disk local: 'serve' => false, agar foto privat hanya bisa diakses lewat
     controller ber-Policy.
3. Autentikasi & role:
   - Nonaktifkan registrasi, lupa password lewat email, dan verifikasi email:
     fitur Fortify, route, halaman, tautan, dan test bawaan terkait. Hapus
     MustVerifyEmail dan middleware 'verified'.
   - Nonaktifkan passkey: hapus dari Fortify, model User, route, halaman,
     komponen, dan config; hapus paket @laravel/passkeys; buat migration BARU
     untuk drop tabel passkeys (jangan edit migration lama).
   - Hapus fitur hapus akun sendiri (tidak ada hard delete).
   - Kolom users: role (enum StaffRole: manager/admin, label Pengelola/Admin),
     is_active, must_change_password, last_login_at — lewat migration baru.
   - Middleware EnsureAccountIsActive (logout akun nonaktif di setiap request),
     EnsurePasswordChanged (paksa ganti password sementara), dan
     EnsureTwoFactorEnabled (bila FLEET_REQUIRE_2FA_FOR_MANAGER=true,
     Pengelola tanpa 2FA diarahkan ke halaman keamanan).
   - Kebijakan password di semua environment: minimal 10 karakter, huruf +
     angka; uncompromised() hanya bila FLEET_PASSWORD_CHECK_UNCOMPROMISED=true.
   - Catat last_login_at saat login.
   - Pastikan rate limit login 5x/menit per email+IP dan 2FA starter kit aktif
     (opsional per akun). QR 2FA ditampilkan sebagai <img> data URI, bukan
     dangerouslySetInnerHTML.
   - Props bersama auth.user hanya berisi field yang dibutuhkan, bukan model utuh.
   - Seeder akun Pengelola pertama dari variabel .env (tambahkan ke
     .env.example), dengan must_change_password = true.
4. Keamanan web: middleware SecurityHeaders (CSP tanpa host eksternal yang
   kompatibel dengan Vite saat dev, X-Frame-Options, X-Content-Type-Options,
   Referrer-Policy, Permissions-Policy camera=(self)). Session lifetime 120 menit.
5. Tema & layout:
   - Font Inter lokal saja: hapus pemanggilan font dari CDN eksternal (bunny)
     dan import ganda di resources/css/app.css.
   - Tambah token success & warning (+ -foreground) di resources/css/app.css
     untuk light dan dark.
   - Komponen StatusBadge terpusat dengan pemetaan status → token sesuai PRD
     section 11.3 (badge berisi teks + ikon).
   - Pastikan pilihan tampilan Light/Dark/Ikuti Sistem berfungsi dan berlabel
     Bahasa Indonesia; tema toast Sonner mengikuti pilihan tampilan (bukan
     next-themes tanpa provider).
   - Halaman / diarahkan ke login/dashboard (hapus welcome page Laravel).
   - Sidebar berisi menu sesuai PRD section 11.4 (halaman belum dibuat cukup
     placeholder "Segera hadir"); menu Kelola Petugas & Log Aktivitas hanya
     tampil untuk Pengelola. Hapus tautan Repository/Documentation Laravel.
     Semua teks UI starter kit diterjemahkan ke Bahasa Indonesia.
   - Sonner sudah terpasang; sambungkan ke flash message Laravel.
6. Test: registrasi, lupa password, verifikasi email, dan passkey tidak
   tersedia; akun nonaktif tidak bisa login & sesinya berakhir; password
   sementara memaksa ganti; kebijakan password; 2FA wajib bagi Pengelola saat
   opsi true (dan tidak saat false); rate limit login; security headers ada;
   menu Pengelola tidak bisa diakses Admin.

Selesai bila semua perintah verifikasi di AGENTS.md section 11 lolos.
Jangan lanjut ke Fase 2. Laporkan: ringkasan, daftar file yang diubah/dibuat,
hasil setiap perintah verifikasi, dan hal yang perlu keputusan saya.
```

**Checklist Verifikasi Fase 1**

- [ ] `php artisan migrate:fresh --seed` berhasil, login dengan akun Pengelola dari `.env` langsung diminta ganti password.
- [ ] Halaman/tautan registrasi tidak ada.
- [ ] Halaman login tidak menampilkan tautan lupa password maupun tombol passkey.
- [ ] Font tidak dimuat dari internet (cek tab Network di DevTools).
- [ ] Light, Dark, dan Ikuti Sistem berfungsi; semua teks berbahasa Indonesia.
- [ ] Menu sidebar sesuai PRD.

---

## Prompt 2 — Akun Petugas

```
Kerjakan FASE 2 — KELOLA PETUGAS sesuai prd.md (FR-01 s.d. FR-05, section 9,
10.1) dan AGENTS.md. Kerjakan HANYA fase ini.

Cakupan:
1. Halaman Kelola Petugas (khusus Pengelola): daftar akun dengan pencarian,
   filter role & status, pagination server, dan kolom status 2FA tiap akun.
2. Action: CreateStaffAccount, UpdateStaffAccount, ResetStaffPassword,
   DeactivateStaffAccount, ActivateStaffAccount — semua lewat FormRequest +
   Policy, tercatat di activity log.
   - Pasang spatie/laravel-activitylog di fase ini.
   - Buat akun & reset password menghasilkan password sementara yang
     ditampilkan SEKALI kepada Pengelola dalam dialog, dengan tombol salin;
     akun wajib ganti password saat login berikutnya.
   - Email unik; email hanya bisa diubah oleh Pengelola.
3. Proteksi: Pengelola tidak bisa menonaktifkan dirinya sendiri; sistem
   menolak penonaktifan/penurunan role yang membuat jumlah Pengelola aktif
   menjadi 0.
4. Halaman Profil: ubah nama & ganti password sendiri (sesuaikan halaman
   settings starter kit; email tidak bisa diubah sendiri).
5. Dialog form tidak tertutup saat klik backdrop; aksi nonaktifkan & reset
   password memakai dialog konfirmasi.
6. Perintah artisan untuk mereset password Pengelola yang terkunci: meminta
   konfirmasi, menghasilkan password sementara (must_change_password = true),
   dan tercatat di activity log. Tidak ada lupa password lewat email.
7. Test lengkap untuk semua aturan di atas, termasuk Admin ditolak mengakses
   seluruh endpoint Kelola Petugas dan perintah artisan reset password.

Jangan lanjut ke Fase 3. Laporkan seperti format sebelumnya.
```

**Checklist Verifikasi Fase 2**

- [ ] Pengelola bisa membuat akun Admin; Admin login dan diminta ganti password.
- [ ] Admin tidak melihat dan tidak bisa membuka Kelola Petugas (coba ketik URL-nya langsung).
- [ ] Pengelola terakhir tidak bisa dinonaktifkan.
- [ ] Status 2FA tiap akun tampil di daftar Kelola Petugas.
- [ ] Perintah artisan reset password Pengelola berfungsi, dan password hasilnya wajib diganti saat login.

---

## Prompt 3 — Data Master

```
Kerjakan FASE 3 — DATA MASTER sesuai prd.md (FR-06 s.d. FR-10, section 3.6,
3.7, 5.3, 8, 10.3) dan AGENTS.md. Kerjakan HANYA fase ini.

Cakupan:
1. Migration, model, factory, enum untuk: cars (operational_status:
   operational/maintenance, is_active), car_photos (position 1/2, unik per
   mobil), employees (employee_number unik, division dari config/fleet.php,
   is_active). Sertakan index & foreign key restrictOnDelete sesuai PRD.
   Buat juga tabel bookings minimal (kolom sesuai PRD section 8) agar proteksi
   "punya peminjaman berjalan" bisa diuji — logika peminjaman dikerjakan di
   Fase 4.
2. Support\ImageProcessor (pasang intervention/image): validasi isi file,
   resize sisi terpanjang 1600 px, re-encode JPEG, buang EXIF, nama UUID,
   simpan via Storage::disk(). Dipakai juga di Fase 4. Pasang juga
   browser-image-compression untuk kompresi foto di browser sebelum unggah.
3. Data Mobil: daftar (pencarian, filter status, pagination), tambah/edit,
   2 foto referensi (disk public, path cars/{uuid}.jpg) dengan ganti foto
   yang menghapus file lama, galeri popup berupa Dialog dengan tombol
   sebelumnya/selanjutnya (tanpa carousel/embla), ubah status operasional,
   aktif/nonaktif.
   - Foto boleh menyusul: mobil bisa dibuat tanpa foto. Slot kosong tampil
     placeholder, dan mobil diberi penanda "Foto belum lengkap" di daftar
     dan detail.
   - Status tampilan mobil diturunkan sesuai PRD 5.3 (scope/accessor terpusat).
   - Tidak bisa set Maintenance saat ada booking active; tampilkan peringatan
     berisi daftar booking scheduled mendatang.
4. Data Pegawai: daftar (pencarian, filter divisi & status, pagination),
   tambah/edit, aktif/nonaktif.
   - NIP: wajib, unik, huruf/angka tanpa spasi (alpha_num:ascii), maks. 30
     karakter, tanpa pola khusus; dinormalisasi ke huruf besar sebelum
     validasi unik dan disimpan huruf besar.
   - No HP: wajib, terima 08xx atau +62xx; normalisasi dan simpan sebagai
     08xx; panjang 10–15 digit dihitung setelah normalisasi.
5. Proteksi FR-10: penonaktifan mobil/pegawai ditolak bila punya booking
   scheduled/active, dengan pesan yang menyebut booking penghalangnya.
6. Semua aksi lewat Action + FormRequest + Policy dan tercatat di activity log.
   Tidak ada tombol hapus permanen.
7. Seeder pengembangan: 8–10 mobil dan 20–30 pegawai dengan data realistis
   Indonesia (plat nomor format Indonesia).
8. Test: validasi unik, format NIP & normalisasi no HP, mobil tanpa foto,
   unggah foto (Storage::fake), proteksi nonaktif, aturan maintenance,
   otorisasi.

Jangan lanjut ke Fase 4. Laporkan seperti format sebelumnya.
```

**Checklist Verifikasi Fase 3**

- [ ] Tambah mobil dengan 2 foto, galeri bisa digeser, tampil benar di light & dark.
- [ ] Mobil tanpa foto tampil dengan placeholder dan penanda "Foto belum lengkap".
- [ ] No HP `+6281234567890` tersimpan sebagai `081234567890`; NIP berisi spasi ditolak; NIP `abc123` tersimpan `ABC123` dan `ABC123` kedua ditolak sebagai duplikat.
- [ ] Plat nomor/NIP duplikat ditolak dengan pesan Bahasa Indonesia.
- [ ] Unggah file bukan gambar (misalnya .pdf yang diganti nama jadi .jpg) ditolak.

---

## Prompt 4 — Inti Peminjaman

```
Kerjakan FASE 4 — INTI PEMINJAMAN sesuai prd.md (FR-11 s.d. FR-19,
section 3.1–3.3, 5, 7.1, 7.2, 10.3, 11.6) dan AGENTS.md section 5.
Ini fase paling kritis; ikuti aturan domain secara ketat.
Kerjakan HANYA fase ini.

Cakupan:
1. Lengkapi skema: bookings (code unik PMJ-YYYYMM-NNNN via
   BookingCodeGenerator di dalam transaksi), booking_photos (stage
   pickup/return, source camera/upload, unik per stage), booking_extensions.
2. Enum BookingStatus dengan label & canTransitionTo() sesuai state machine
   PRD 5.1. Scope: overlapping, overdue, notPickedUp, completedLate.
3. Action (masing-masing dalam DB::transaction + lockForUpdate baris mobil,
   tercatat di activity log):
   - ScheduleBooking & UpdateBookingSchedule: validasi pegawai aktif, mobil
     aktif & operasional, rencana kembali > mulai, waktu mulai tidak lebih
     awal dari now() dikurangi toleransi di config/fleet.php (15 menit),
     cek bentrok PRD 5.2 (termasuk booking active terlambat dianggap
     berakhir = now()). Error bentrok menyebut kode & rentang booking yang
     bertabrakan.
   - UpdateBookingSchedule: hanya scheduled; boleh mengganti mobil, pegawai,
     waktu mulai, rencana kembali, dan tujuan; toleransi waktu mulai HANYA
     divalidasi bila start_at diubah (booking Belum Diambil tetap bisa
     diedit tanpa mengubah start_at); cek bentrok setiap kali mobil atau
     waktu berubah; catat nilai sebelum/sesudah di activity log.
   - CancelBooking: hanya scheduled, alasan wajib.
   - PickUpBooking: hanya scheduled, tepat 1 foto, boleh sebelum start_at
     (picked_up_at = waktu aktual). Ditolak bila mobil maintenance atau
     mobil masih punya booking active lain (peminjaman sebelumnya belum
     dikembalikan). Simpan via ImageProcessor ke disk privat
     bookings/{Y}/{m}/{uuid}.jpg, taken_at = waktu server.
   - ExtendBooking: hanya active, alasan wajib, rencana baru > rencana lama,
     cek bentrok, simpan ke booking_extensions.
   - ReturnBooking: hanya active, tepat 1 foto, catatan kondisi opsional.
4. Route penyaji foto bukti yang mewajibkan login + Policy (tidak ada akses
   langsung ke file).
5. Halaman:
   - Peminjaman: daftar booking berjalan (scheduled & active) dengan aksi
     sesuai status.
   - Form jadwal: pilih pegawai (combobox dengan pencarian; pasang lewat
     npx shadcn@latest add command), rentang waktu dengan input
     datetime-local bawaan browser, lalu combobox mobil yang menandai
     ketersediaan pada rentang tersebut.
   - Detail: data lengkap, riwayat perpanjangan, foto sebelum & sesudah
     berdampingan.
   - Komponen PhotoCapture: Ambil dengan Kamera (getUserMedia, tangani izin
     ditolak/kamera tidak ada dengan pesan jelas) atau Unggah File; pratinjau;
     ambil ulang; kompresi browser-image-compression (target ≤300 KB)
     sebelum kirim.
6. Test wajib (PRD 14.1): bentrok (termasuk dengan booking terlambat dan
   batas tepat bersentuhan tidak dianggap bentrok), transisi tidak valid,
   serah terima tanpa foto/foto tidak valid, perpanjangan bentrok, mobil
   maintenance tidak bisa diambil, foto bukti tidak bisa diakses tamu,
   kode booking unik & berurutan per bulan, waktu mulai di masa lalu
   (di dalam & di luar toleransi), edit booking Belum Diambil tanpa
   mengubah start_at tetap berhasil, serah terima ambil ditolak selama mobil
   belum kembali dari booking sebelumnya, perpanjangan yang tidak lebih
   lambat ditolak, edit jadwal mencatat nilai sebelum/sesudah. Gunakan
   travelTo() untuk waktu.

Jangan lanjut ke Fase 5. Laporkan seperti format sebelumnya.
```

**Checklist Verifikasi Fase 4**

- [ ] Jadwalkan 2 peminjaman bertabrakan untuk mobil yang sama → yang kedua ditolak.
- [ ] Alur lengkap: jadwalkan → ambil (foto webcam) → perpanjang → kembali (foto upload) → detail menampilkan 2 foto berdampingan.
- [ ] Salin URL foto bukti, buka di jendela incognito → harus diarahkan ke login.
- [ ] Jadwal mendatang untuk mobil yang belum kembali bisa dibuat, tetapi serah terima ambilnya ditolak sampai mobil dikembalikan.

---

## Prompt 5 — Monitoring

```
Kerjakan FASE 5 — MONITORING sesuai prd.md (FR-20 s.d. FR-24, section 3.4,
3.5, 7.3) dan AGENTS.md. Kerjakan HANYA fase ini.

Cakupan:
1. Dashboard: kartu ringkasan armada (Tersedia, Dipinjam, Maintenance,
   Nonaktif), jadwal ambil & kembali hari ini, daftar Terlambat (lama
   keterlambatan "x hari y jam" + no HP pegawai), daftar Belum Diambil.
   Setiap item tertaut ke detail peminjaman.
2. Badge jumlah Terlambat & Belum Diambil di sidebar, dibagikan lewat shared
   prop yang efisien, diperbarui dengan polling Inertia yang hanya memuat
   ulang prop tersebut (interval dari config/fleet.php).
3. FR-23: tandai booking scheduled berikutnya untuk mobil yang belum kembali
   (di daftar Peminjaman, detail, dan dashboard).
4. Jadwal Armada: timeline mingguan per mobil (baris = mobil, kolom = hari),
   navigasi minggu sebelumnya/berikutnya, blok booking berwarna sesuai status
   dan dapat diklik. Tetap terbaca di light & dark.
5. Query efisien: tanpa N+1, gunakan index yang ada; cek dengan data seeder
   yang lebih besar (tambahkan factory state untuk ±1.000 booking historis).
6. Test: perhitungan Terlambat/Belum Diambil di batas waktu, isi dashboard,
   badge count.

Jangan lanjut ke Fase 6. Laporkan seperti format sebelumnya.
```

**Checklist Verifikasi Fase 5**

- [ ] Buat peminjaman dengan rencana kembali beberapa menit lagi, tunggu lewat → badge Terlambat bertambah tanpa refresh halaman.
- [ ] Jadwal Armada menampilkan peminjaman di hari yang benar.

---

## Prompt 6 — Riwayat, Laporan & Log Aktivitas

```
Kerjakan FASE 6 — RIWAYAT, LAPORAN & LOG AKTIVITAS sesuai prd.md (FR-25 s.d.
FR-27, section 3.8, 3.9) dan AGENTS.md. Kerjakan HANYA fase ini.

Cakupan:
1. Halaman Riwayat: pencarian (kode, nama pegawai, plat) dan filter periode,
   mobil, pegawai, divisi, status, keterlambatan. Filter tersimpan di query
   string agar bisa dibagikan/di-refresh.
2. Ekspor Excel (.xlsx) dan CSV (pasang maatwebsite/excel) lewat Action
   ExportBookings: mengikuti filter aktif, kolom sesuai PRD 3.8, tanggal
   format Indonesia, header Bahasa Indonesia, nama file berisi periode &
   tanggal ekspor. Gunakan FromQuery + chunking. Ekspor tercatat di activity
   log.
3. Halaman Log Aktivitas (khusus Pengelola): daftar dengan filter petugas,
   jenis data, dan periode; tampilkan perubahan nilai sebelum/sesudah dalam
   bahasa yang mudah dipahami. Tidak ada aksi ubah/hapus log.
4. Test: hasil ekspor sesuai filter (isi baris & kolom), Admin ditolak
   mengakses log, filter riwayat.

Jangan lanjut ke Fase 7. Laporkan seperti format sebelumnya.
```

**Checklist Verifikasi Fase 6**

- [ ] File Excel dan CSV terbuka rapi di Excel, termasuk karakter dan tanggal Indonesia.
- [ ] Isi ekspor sama dengan data yang tampil sesuai filter.

---

## Prompt 7 — Audit Kualitas & Keamanan

```
Kerjakan FASE 7 — AUDIT KUALITAS & KEAMANAN. Tujuannya MENEMUKAN dan
MEMPERBAIKI masalah, bukan menambah fitur. Kerjakan HANYA fase ini.

Lakukan dan laporkan temuan per poin sebelum memperbaiki:
1. Kepatuhan PRD: periksa setiap FR dan NFR di prd.md — tandai Sesuai /
   Sebagian / Belum, dengan bukti (file/test).
2. Kepatuhan AGENTS.md: logika bisnis di luar Action, controller gemuk,
   $request->all(), $guarded kosong, raw query, props Inertia berisi model
   utuh/kolom sensitif, warna Tailwind langsung, env() di luar config,
   hard delete, dependensi tak terpakai, aset/font dari CDN eksternal, sisa
   registrasi/lupa password/verifikasi email/passkey.
3. Keamanan: setiap route terlindungi auth + Policy (buat test yang mengecek
   seluruh route terdaftar), akses foto bukti, unggah file berbahaya,
   CSRF, security headers, rate limit, mass assignment, IDOR (akses ID lain
   via URL), pesan error tidak membocorkan detail teknis dengan APP_DEBUG=false.
   Jalankan composer audit dan npm audit.
4. Performa: N+1 di setiap halaman daftar, index sesuai query, waktu respons
   dengan ±10.000 booking.
5. UI/UX: semua halaman di light & dark, lebar 1280 px dan 768 px, empty &
   loading state, teks Bahasa Indonesia tanpa jargon, dialog konfirmasi,
   backdrop tidak menutup form.
6. Cakupan test: tambahkan test untuk aturan bisnis yang belum tercakup.

Setelah laporan, perbaiki semua temuan prioritas tinggi & sedang, lalu jalankan
seluruh perintah verifikasi. Laporkan temuan yang tersisa beserta alasannya.
```

**Checklist Verifikasi Fase 7**

- [ ] Laporan kepatuhan FR/NFR semuanya "Sesuai", atau ada alasan jelas untuk yang belum.
- [ ] Set `APP_DEBUG=false`, buka URL yang tidak ada dan ID yang tidak ada → muncul halaman error ramah, tanpa detail teknis.

---

## Prompt 8 — Operasional & Serah Terima

```
Kerjakan FASE 8 — OPERASIONAL & SERAH TERIMA sesuai prd.md section 13 dan
NFR-05/06. Kerjakan HANYA fase ini.

Cakupan:
1. Backup: pasang & konfigurasi spatie/laravel-backup (database + folder foto
   privat & publik), jadwal harian, retensi minimal 30 hari, tujuan backup
   lewat .env (drive/folder terpisah). Perintah artisan untuk menjalankan
   backup manual dan cek status.
2. Scheduler: definisikan semua jadwal di routes/console.php; buat skrip .bat
   untuk Windows Task Scheduler yang menjalankan schedule:run tiap menit, dan
   jika ada queue, worker yang aman dijalankan ulang.
3. Perintah artisan perawatan: buat akun Pengelola darurat (meminta
   konfirmasi, mencatat ke log). Perintah reset password Pengelola sudah
   dibuat di Fase 2; pastikan terdokumentasi di README.
4. Halaman error kustom Bahasa Indonesia (403, 404, 419, 500, 503) sesuai tema.
5. .env.example lengkap dengan komentar setiap variabel; konfigurasi
   production terpisah (APP_ENV=production, APP_DEBUG=false, LOG_LEVEL,
   SESSION_SECURE_COOKIE, APP_URL, APP_TIMEZONE, FLEET_*).
6. README.md Bahasa Indonesia untuk staf IT non-developer:
   - Instalasi dari nol di Laragon (langkah demi langkah)
   - Membuat user MySQL khusus aplikasi (bukan root) dan mengganti .env
   - Mengaktifkan autostart Laragon & SSL lokal agar webcam berfungsi,
     termasuk rename folder sistem_peminjaman_mobil → sistem-peminjaman-mobil
     (garis bawah tidak valid untuk hostname) dan
     APP_URL=https://sistem-peminjaman-mobil.test
   - Mendaftarkan Task Scheduler
   - Backup, restore (diuji: lakukan restore ke database uji dan
     dokumentasikan hasilnya), dan lokasi folder foto
   - Membuat/mereset akun Pengelola
   - Menambah pilihan divisi
   - Update aplikasi dan bagian "Pindah ke server" (PRD 13.2), termasuk
     anjuran FLEET_REQUIRE_2FA_FOR_MANAGER=true dan
     FLEET_PASSWORD_CHECK_UNCOMPROMISED=true bila sistem dapat diakses dari
     internet
   - Troubleshooting umum
7. Panduan pengguna singkat untuk petugas GA (docs/panduan-pengguna.md):
   alur harian dengan bahasa awam.
8. Build production: npm run build, php artisan optimize; pastikan aplikasi
   berjalan normal dalam mode production.

Laporkan seperti format sebelumnya, ditambah daftar langkah manual yang harus
saya lakukan di PC kantor.
```

**Checklist Verifikasi Fase 8 (Siap Pakai)**

- [ ] User MySQL khusus sudah dibuat dan `.env` tidak lagi memakai `root`.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, aset hasil `npm run build`.
- [ ] Laragon autostart; aplikasi bisa dibuka setelah PC restart.
- [ ] Backup harian berjalan, dan restore sudah diuji.
- [ ] Folder sudah di-rename; aplikasi terbuka di `https://sistem-peminjaman-mobil.test`.
- [ ] Webcam berfungsi di browser PC kantor.
- [ ] Akun Pengelola asli dibuat, akun seeder/development dinonaktifkan, password diganti, 2FA Pengelola aktif.
- [ ] Data dummy seeder dikosongkan (`migrate:fresh` + seeder produksi saja), lalu data mobil dan pegawai asli dimasukkan.
- [ ] README dan panduan pengguna diserahkan ke GA/IT kantor.

---

## Prompt Pendukung

### Melanjutkan Sesi Baru

```
Baca prd.md dan AGENTS.md. Periksa riwayat git (git log --oneline -20) dan
kondisi kode saat ini. Kita sedang di FASE [N] — [NAMA FASE] di PROMPTS.md.
Laporkan bagian fase ini yang sudah dan belum selesai, lalu lanjutkan bagian
yang belum. Jangan mengulang pekerjaan yang sudah ada.
```

### Perbaikan Bug

```
Ada masalah: [jelaskan apa yang dilakukan, apa yang diharapkan, apa yang
terjadi, pesan error bila ada].

1. Temukan penyebab utamanya, jelaskan sebelum memperbaiki.
2. Tulis test yang mereproduksi bug (harus gagal dulu).
3. Perbaiki dengan perubahan sekecil mungkin sesuai AGENTS.md.
4. Jalankan seluruh perintah verifikasi.
Jangan mengubah hal di luar masalah ini.
```

### Perubahan Kebutuhan

```
Saya ingin mengubah/menambah: [jelaskan].

Sebelum menulis kode:
1. Jelaskan dampaknya terhadap prd.md, skema database, aturan domain, dan
   fitur yang sudah ada.
2. Usulkan perubahan teks prd.md (dan AGENTS.md bila perlu).
Tunggu persetujuan saya sebelum mengubah dokumen maupun kode.
```

### Review Sebelum Commit

```
Review semua perubahan yang belum di-commit (git diff) terhadap AGENTS.md:
arsitektur, aturan domain, keamanan, UI, dan test. Laporkan pelanggaran
beserta perbaikannya. Jangan menambah fitur baru.
```
