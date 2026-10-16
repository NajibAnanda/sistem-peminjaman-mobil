# AGENTS.md

Panduan wajib untuk setiap AI coding agent (dan developer) yang bekerja di repository ini.
Sumber kebenaran kebutuhan produk adalah **`prd.md`**. Jika ada konflik antara permintaan dan PRD, **berhenti dan tanyakan**, jangan menebak.

---

## 1. Ringkasan Project

Sistem peminjaman mobil dinas internal yang **hanya dioperasikan petugas GA** (role `manager` = "Pengelola" dan `admin` = "Admin"). Karyawan peminjam **tidak login**; mereka hanya data master.

Alur inti: **Dijadwalkan → Aktif (serah terima ambil, 1 foto) → Selesai (serah terima kembali, 1 foto)**, dengan cabang **Dibatalkan** dan **Perpanjangan**. Status *Terlambat* dan *Belum Diambil* **dihitung, tidak disimpan**.

Berjalan lokal di PC kantor (Laragon, Windows), tetapi **harus selalu siap deploy ke server hanya dengan mengganti `.env`**.

Hal yang **tidak ada** di sistem ini dan **tidak boleh ditambahkan tanpa permintaan eksplisit**: login karyawan, registrasi, lupa password lewat email, verifikasi email, passkey, approve/tolak, odometer, jarak tempuh, tanda tangan digital, notifikasi WhatsApp/email, ekspor PDF.

---

## 2. Tech Stack

| Area | Teknologi |
| ---- | --------- |
| Backend | Laravel 13, PHP sesuai syarat Laravel 13 |
| Frontend | React + TypeScript (strict) via Inertia.js |
| Routing frontend | Laravel Wayfinder (fungsi route bertipe) |
| UI | shadcn/ui (preset Nova + Neutral + Blue), Tailwind CSS v4, Lucide, font Inter lokal (`@fontsource-variable/inter`) |
| Auth | Laravel Fortify: login + 2FA. Registrasi, lupa password, verifikasi email, dan passkey dinonaktifkan |
| Bahasa | `lang/id/` ditulis manual (tanpa paket terjemahan) |
| Database | MySQL 8 |
| File | Laravel Filesystem: disk `local` (privat) & `public` |
| Paket | `intervention/image`, `maatwebsite/excel`, `spatie/laravel-activitylog`, `spatie/laravel-backup`, `browser-image-compression`, `cmdk` (lewat `npx shadcn@latest add command`) |
| Kualitas | Pest, Larastan, Laravel Pint, oxlint + oxfmt (Vite+, bawaan starter kit) — **tanpa** ESLint/Prettier |

**Jangan menambah dependensi baru** (Composer maupun npm) tanpa alasan jelas dan persetujuan. Utamakan fitur bawaan Laravel/React/shadcn. Paket di tabel di atas sudah disetujui; dipasang pada fasenya masing-masing (lihat `PROMPTS.md`).

---

## 3. Perintah

```bash
composer run dev              # server + vite + queue + log → http://localhost:8000
php artisan test              # semua test (Pest), DB MySQL terpisah peminjaman_mobil_test
composer test                 # pint --test + phpstan + php artisan test
vendor/bin/pint               # format PHP
vendor/bin/phpstan analyse    # analisis statis (Larastan, level 7)
npm run check                 # lint (oxlint) + cek format (oxfmt) via Vite+
npm run check:fix             # perbaiki lint/format otomatis
npm run types:check           # cek TypeScript
npm run build                 # build aset frontend
php artisan migrate:fresh --seed   # reset DB lokal (JANGAN di production)
```

Cek `composer.json` dan `package.json` untuk nama script yang berlaku. Tidak ada script `lint`/`format`/`types` versi ESLint/Prettier; jangan menambahkannya.

---

## 4. Arsitektur

Monolith modular. **Tidak ada REST API terpisah**; semua halaman lewat Inertia.

### 4.1 Lapisan dan Aturannya

```
Route → Middleware → Controller → (FormRequest, Policy) → Action → Model/Enum → DB/Storage
```

| Lapisan | Wajib | Dilarang |
| ------- | ----- | -------- |
| **Controller** | Tipis. Validasi lewat FormRequest, otorisasi lewat `Gate`/`authorize`, panggil satu Action, kembalikan `Inertia::render` atau redirect | Logika bisnis, query kompleks, `DB::` langsung, manipulasi file |
| **FormRequest** | Semua validasi input + pesan error Bahasa Indonesia; `authorize()` memanggil Policy | Logika bisnis |
| **Policy** | Semua keputusan hak akses per model | — |
| **Action** (`app/Actions/{Domain}/`) | Satu kelas = satu proses bisnis, satu method publik `handle()`, dependensi via constructor injection. Operasi multi-langkah dibungkus `DB::transaction()` | Akses `request()`, `session()`, `auth()` langsung — terima data sebagai parameter |
| **Model** | Relasi, `casts()`, scope query (`overdue`, `notPickedUp`, `overlapping`), atribut `#[Fillable([...])]` eksplisit (dan `#[Hidden([...])]` untuk kolom sensitif) | Proses multi-langkah, panggilan ke Storage |
| **Enum** (`app/Enums/`) | Backed enum string, method `label()` Bahasa Indonesia, aturan transisi (`canTransitionTo()`) | — |
| **Support** (`app/Support/`) | Helper murni kecil: `ImageProcessor`, `BookingCodeGenerator` | State global |

Tidak memakai repository pattern, service locator, atau abstraksi tambahan di atas Eloquent. Jangan membuat "BaseService", "BaseRepository", atau interface yang hanya punya satu implementasi.

### 4.2 Contoh Pola

```php
// app/Http/Controllers/BookingPickupController.php
final class BookingPickupController extends Controller
{
    public function store(PickUpBookingRequest $request, Booking $booking, PickUpBooking $action): RedirectResponse
    {
        $action->handle($booking, $request->file('photo'), $request->photoSource(), $request->user());

        return to_route('bookings.show', $booking)->with('success', 'Serah terima ambil berhasil disimpan.');
    }
}
```

```php
// app/Actions/Booking/PickUpBooking.php
final class PickUpBooking
{
    public function __construct(private readonly ImageProcessor $images) {}

    public function handle(Booking $booking, UploadedFile $photo, PhotoSource $source, User $staff): Booking
    {
        return DB::transaction(function () use ($booking, $photo, $source, $staff) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $booking->status->canTransitionTo(BookingStatus::Active)) {
                throw BookingException::invalidTransition($booking->status, BookingStatus::Active);
            }
            // ... cek mobil tidak maintenance, simpan foto via ImageProcessor, update status, log aktivitas
            return $booking;
        });
    }
}
```

Error bisnis dilempar sebagai exception domain (`app/Exceptions/`) dengan pesan Bahasa Indonesia, lalu dipetakan menjadi error validasi atau flash message yang ramah pengguna. Jangan pernah menampilkan pesan exception mentah atau stack trace ke pengguna.

### 4.3 Struktur Folder

```
app/Actions/{Booking,Car,Employee,Staff,Report}/
app/Console/Commands/     # perintah artisan, mis. reset password Pengelola yang terkunci
app/Enums/
app/Exceptions/
app/Exports/
app/Http/Controllers/
app/Http/Middleware/      # EnsureAccountIsActive, EnsurePasswordChanged, EnsureTwoFactorEnabled, SecurityHeaders
app/Http/Requests/{Booking,Car,Employee,Staff}/
app/Models/
app/Policies/
app/Support/
config/fleet.php          # divisi, batas foto, interval polling, toleransi waktu mulai, opsi keamanan (FLEET_*)
lang/id/                  # validation, auth, passwords, pagination + nama atribut (Bahasa Indonesia)
resources/js/components/ui/   # shadcn — jangan diedit kecuali benar-benar perlu
resources/js/components/      # komponen aplikasi
resources/js/layouts/
resources/js/pages/{dashboard,bookings,schedule,history,cars,employees,staff,activity-log,settings}/
resources/js/types/
```

Satu controller per resource; untuk aksi non-CRUD gunakan controller terpisah satu-aksi (mis. `BookingPickupController`, `BookingReturnController`, `BookingExtensionController`, `BookingCancellationController`).

---

## 5. Aturan Domain (Tidak Boleh Dilanggar)

1. **Status tersimpan hanya:** `scheduled`, `active`, `completed`, `cancelled` (enum `BookingStatus`). Transisi valid:
   - `scheduled → active` (serah terima ambil, tepat 1 foto)
   - `scheduled → cancelled` (alasan wajib)
   - `active → completed` (serah terima kembali, tepat 1 foto)
   - Edit jadwal hanya saat `scheduled`; perpanjangan hanya saat `active`.
   - Semua transisi lain **ditolak**.
2. **Indikator turunan dihitung lewat scope, tidak disimpan:**
   - Terlambat: `active` dan `planned_return_at < now()`
   - Belum Diambil: `scheduled` dan `start_at < now()`
   - Selesai terlambat: `completed` dan `returned_at > planned_return_at`
3. **Bentrok jadwal:** `new.start_at < existing.end AND new.planned_return_at > existing.start_at`, hanya terhadap `scheduled`/`active`. Untuk `active` yang terlambat, `existing.end = now()`. Selalu dalam `DB::transaction()` dengan `lockForUpdate()` pada baris mobil. Berlaku untuk jadwal baru, edit jadwal, dan perpanjangan. Konsekuensi yang disengaja: jadwal mendatang untuk mobil yang sedang terlambat tetap boleh (dengan peringatan FR-23), tetapi serah terima ambilnya ditolak sampai mobil kembali (aturan 12).
4. **Status tampilan mobil diturunkan:** Nonaktif → Maintenance → Dipinjam (ada booking `active`) → Tersedia. Kolom `cars.operational_status` hanya `operational`/`maintenance`. Jangan menambahkan kolom "dipinjam".
5. **Tidak ada hard delete** untuk booking, foto bukti, karyawan, mobil, akun petugas, atau log. Gunakan `is_active`. Foreign key memakai `restrictOnDelete()`.
6. Karyawan/mobil dengan booking `scheduled`/`active` **tidak boleh dinonaktifkan**.
7. Minimal **1 akun `manager` aktif** harus selalu ada; Pengelola tidak bisa menonaktifkan dirinya sendiri.
8. Waktu dari server (`now()`), zona waktu `Asia/Jakarta` lewat `APP_TIMEZONE` (dibaca `config/app.php`). Bandingkan waktu dengan `now()` dari PHP yang di-bind ke query, bukan `NOW()` SQL, agar tidak bergantung pada zona waktu MySQL. Timestamp foto = waktu server saat disimpan.
9. Kode booking unik `PMJ-YYYYMM-NNNN`, dibuat di dalam transaksi.
10. Setiap aksi yang mengubah data dicatat ke activity log (siapa, apa, kapan, nilai sebelum/sesudah bila relevan). Pencatatan dimulai sejak Fase 2; halaman Log Aktivitas dibuat di Fase 6.
11. **Waktu mulai:** tidak boleh lebih awal dari `now()` dikurangi toleransi di `config/fleet.php` (default 15 menit). Berlaku untuk jadwal baru, dan untuk edit jadwal **hanya bila `start_at` diubah** — booking Belum Diambil tetap bisa diedit (tujuan, karyawan, mobil, rencana kembali) tanpa mengubah `start_at`.
12. **Serah terima ambil** boleh sebelum `start_at` (`start_at` tetap rencana, `picked_up_at` = waktu aktual), tetapi **ditolak** bila mobil `maintenance` atau mobil masih memiliki booking `active` lain (peminjaman sebelumnya belum dikembalikan).
13. **Edit jadwal** (hanya `scheduled`) boleh mengganti mobil, karyawan, `start_at`, `planned_return_at`, dan tujuan. Cek bentrok dijalankan ulang setiap kali `car_id`, `start_at`, atau `planned_return_at` berubah; nilai sebelum/sesudah dicatat di activity log.
14. **Perpanjangan:** rencana kembali baru wajib `>` `planned_return_at` saat ini. Tidak boleh memperpendek.
15. **Foto referensi mobil** boleh menyusul (0–2 foto). Slot kosong menampilkan placeholder, dan mobil diberi penanda "Foto belum lengkap" di daftar dan detail.
16. **Plat nomor** disimpan dalam satu format baku: huruf besar dengan spasi pemisah (`App\Support\PlateNumber::normalize`, mis. "B 1234 ABC"), divalidasi pola Indonesia (kode wilayah 1–2 huruf, 1–4 angka, akhiran 0–3 huruf), dan keunikan dicek pada format baku itu. Tampilkan nilai baku yang tersimpan apa adanya di **semua** tempat (tabel, detail, dropdown, laporan Excel/CSV); jangan memformat ulang di frontend.
17. **Data karyawan:** NIP wajib, unik, hanya A–Z/a–z/0–9 tanpa spasi (`alpha_num:ascii`), maks. 30 karakter, tanpa pola khusus; dinormalisasi ke **huruf besar** sebelum validasi unik dan disimpan huruf besar. No HP wajib, diterima sebagai `08xx` atau `+62xx`, dinormalisasi dan disimpan sebagai `08xx`; panjang 10–15 digit dihitung **setelah** normalisasi.

---

## 6. Keamanan

Keamanan adalah syarat, bukan fitur tambahan. Setiap perubahan harus mematuhi daftar ini.

### 6.1 Autentikasi & Otorisasi
- Semua route di belakang `auth` kecuali login dan tantangan 2FA Fortify. **Registrasi, lupa password lewat email, verifikasi email, dan passkey nonaktif** — jangan mengaktifkannya kembali (route, UI, tautan, maupun config).
- Middleware `EnsureAccountIsActive` di setiap request terautentikasi; akun nonaktif di-logout.
- Middleware `EnsurePasswordChanged` memaksa ganti password sementara.
- Middleware `EnsureTwoFactorEnabled`: bila `FLEET_REQUIRE_2FA_FOR_MANAGER=true`, Pengelola tanpa 2FA terkonfirmasi diarahkan ke halaman keamanan sampai 2FA aktif. Default `false` (2FA opsional untuk semua akun).
- Kebijakan password sama di semua environment: minimal 10 karakter, huruf + angka (`Password::defaults()`). Cek password bocor (`uncompromised()`, butuh internet) hanya aktif bila `FLEET_PASSWORD_CHECK_UNCOMPROMISED=true`.
- Reset password hanya oleh Pengelola (password sementara + `must_change_password`). Pengelola yang terkunci direset lewat perintah artisan.
- **Setiap** aksi yang membaca/mengubah data melewati Policy. Menyembunyikan tombol di UI **bukan** otorisasi.
- Fitur Pengelola (`staff`, `activity-log`) dilindungi Policy/Gate, bukan hanya menu.
- Rate limit login dipertahankan. Jangan melemahkan konfigurasi Fortify.

### 6.2 Input & Data
- Semua input divalidasi di FormRequest. Jangan pernah memakai `$request->all()` untuk create/update — gunakan `$request->validated()`.
- `$fillable` eksplisit di setiap model; **dilarang** `$guarded = []`.
- Hanya Eloquent/query builder dengan binding. **Dilarang** `DB::raw`/`whereRaw` dengan input pengguna.
- **Dilarang** `dangerouslySetInnerHTML` di React, termasuk untuk QR 2FA (tampilkan sebagai `<img src="data:image/svg+xml;base64,...">`).
- Jangan pernah mengirim kolom sensitif ke frontend (password, `two_factor_secret`, `remember_token`). Batasi props Inertia hanya pada field yang dibutuhkan halaman — gunakan `->only()`/resource/array eksplisit, bukan model utuh.

### 6.3 Unggah File
- Validasi: `file`, `mimes:jpg,jpeg,png,webp`, `mimetypes` sesuai, `max` dari `config/fleet.php`.
- Semua gambar **wajib** lewat `ImageProcessor`: resize (sisi terpanjang 1600 px), re-encode, buang EXIF.
- Nama file selalu UUID. **Jangan pernah** memakai nama file asli.
- Foto bukti di disk `local` (privat) path `bookings/{Y}/{m}/{uuid}.jpg`; hanya disajikan lewat controller yang memanggil Policy. Foto referensi mobil di disk `public` path `cars/{uuid}.jpg`.
- Selalu akses file lewat `Storage::disk(...)`. Jangan hardcode path absolut atau `public_path()` untuk foto.

### 6.4 Konfigurasi & Rahasia
- Tidak ada rahasia di kode. Semua lewat `.env` dan dibaca via `config()` (jangan panggil `env()` di luar folder `config/`).
- `.env` tidak boleh di-commit; perbarui `.env.example` (dengan komentar) bila menambah variabel. Variabel `FLEET_*` dibaca lewat `config/fleet.php`.
- Development lokal sengaja memakai MySQL `root` dengan password kosong; jangan ubah kredensial `.env`. User MySQL khusus dibuat di Fase 8.
- Lingkungan: development `http://localhost:8000` (`composer run dev`); PC kantor `https://sistem-peminjaman-mobil.test` (SSL Laragon, folder di-rename di Fase 8); server lewat `.env` dengan HTTPS wajib.
- Middleware `SecurityHeaders`: CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(self)`. Tidak ada aset dari CDN eksternal (font dibundel lokal), sehingga CSP cukup origin sendiri (plus server Vite saat development).
- Jangan pernah log password, token, atau isi file.

---

## 7. Database

- Semua perubahan skema lewat **migration baru**. Jangan mengedit migration yang sudah dijalankan/di-commit; buat migration baru.
- Nama tabel/kolom bahasa Inggris, `snake_case`, tabel jamak.
- Tipe tepat: `datetime` untuk waktu kejadian, `boolean` untuk flag, `string` dengan panjang wajar, `text` untuk catatan.
- Index sesuai PRD section 8.3. Tambah index bila menambah filter/query baru yang sering dipakai.
- Cegah N+1: gunakan eager loading (`with()`), aktifkan `Model::preventLazyLoading()` di non-production.
- Daftar besar selalu `paginate()` di server. Ekspor memakai chunking/`FromQuery`.
- Test memakai database MySQL terpisah `peminjaman_mobil_test` (diatur di `phpunit.xml`). Jangan pernah mengarahkan test ke database aplikasi, karena `RefreshDatabase` mengosongkan tabel.
- Setiap model punya factory; seeder pengembangan menyediakan data realistis. Seeder akun Pengelola pertama membaca kredensial dari `.env` dan menandai `must_change_password = true`.

---

## 8. Frontend

### 8.1 TypeScript & React
- TypeScript strict. Hindari `any`; bila terpaksa, beri komentar alasan.
- Definisikan tipe props setiap halaman di `resources/js/types/`, sinkron dengan data yang dikirim controller.
- Gunakan route bertipe dari Wayfinder; **jangan** hardcode URL string.
- Form memakai `useForm` Inertia; tampilkan error validasi dari server per field.
- Input waktu memakai `<input type="datetime-local">` bawaan browser (tanpa pemilih tanggal pihak ketiga).
- Pemilihan karyawan dan mobil memakai combobox dari shadcn `command` (`cmdk`).
- Komponen function, kecil, satu tanggung jawab. Logika berulang dipindah ke hook (`resources/js/hooks/`).
- Badge Terlambat/Belum Diambil diperbarui dengan polling Inertia yang hanya memuat ulang prop terkait (interval dari config).

### 8.2 shadcn/ui & Styling
- Komponen ditambah hanya lewat `npx shadcn@latest add <nama>`. Jangan menyalin komponen dari sumber lain.
- Jangan mengedit `resources/js/components/ui/*` kecuali perlu; jika perlu, catat alasannya di commit.
- **Hanya token semantik**: `bg-background`, `bg-card`, `bg-primary`, `text-muted-foreground`, `border-border`, `bg-destructive`, `bg-success`, `bg-warning`, dst. **Dilarang** warna langsung (`bg-blue-600`, `text-red-500`, hex di className).
- Token baru (mis. `success`, `warning`) didefinisikan di `resources/css/app.css` untuk **light dan dark**.
- Pemetaan status → token mengikuti PRD section 11.3, dipusatkan di satu komponen `StatusBadge`. Badge selalu berisi teks (dan ikon), bukan hanya warna.
- Setiap halaman harus tampil benar di **light dan dark mode**.
- Font: Inter (`@fontsource-variable/inter`) dibundel lokal. Jangan memuat font atau aset apa pun dari CDN eksternal.

### 8.3 UX
- Semua teks antarmuka Bahasa Indonesia, tanpa jargon teknis (kode FR, nama tabel, path storage, nama enum).
- Dialog berisi form/unggahan tidak tertutup saat klik backdrop.
- Aksi berisiko (batalkan, nonaktifkan, reset password) wajib dialog konfirmasi.
- Feedback memakai toast (Sonner). Setiap daftar punya empty state dan loading state.
- Format tanggal Indonesia (mis. `16 Okt 2026, 14.30`), satu helper format terpusat.
- Galeri foto referensi mobil: Dialog dengan tombol sebelumnya/selanjutnya, tanpa carousel/embla. Slot foto kosong menampilkan placeholder dan penanda "Foto belum lengkap".
- Komponen `PhotoCapture`: opsi kamera (`getUserMedia`) dan unggah file, pratinjau, ambil ulang, kompresi `browser-image-compression` sebelum kirim.

### 8.4 Pola Tabel (wajib untuk SEMUA tabel)

Setiap daftar data (Kelola Petugas, Data Mobil, Data Karyawan, Peminjaman, Riwayat, Log Aktivitas) **wajib** memakai komponen `resources/js/components/data-table/` dan hook `useTableQuery`. Jangan membuat tabel, filter, atau pagination sendiri. Contoh acuan: `pages/staff/index.tsx`.

**Struktur halaman daftar:**
```
┌─ PageHeader: Judul halaman + deskripsi ............ [Aksi utama] ─┐   ← di LUAR card
┌─ DataTableCard ────────────────────────────────────────────────────┐
│ Judul tabel / deskripsi              [🔍 Cari...] [Filter 2]       │   ← header card, border-b
├────────────────────────────────────────────────────────────────────┤
│ #  Kolom ...                                              ⋯        │   ← tabel
├────────────────────────────────────────────────────────────────────┤
│ Menampilkan 11–20 dari 47 data                    « ‹ 1 2 3 4 5 › » │   ← footer, border-t
└────────────────────────────────────────────────────────────────────┘
```
1. **Header halaman** (`PageHeader`): judul + deskripsi di kiri, **aksi utama** (mis. "Tambah Petugas") di kanan — di luar dan di atas card. Card tabel **tidak** berisi tombol aksi utama.
2. **Header card:** kiri = judul tabel (mis. "Daftar Petugas") + deskripsi opsional satu baris (`text-muted-foreground`); kanan = input pencarian lalu tombol **Filter**. Sejajar vertikal di tengah; bila card sempit (container query `@container/table`, bukan lebar layar, karena sidebar ikut memakan ruang) pencarian/filter turun ke bawah judul dan melebar penuh. Dipisah dari tabel dengan `border-b`. Filter = satu `DropdownMenu` berisi grup `DropdownMenuLabel` + `DropdownMenuCheckboxItem` (multi-pilih, tidak menutup saat dicentang), dipisah `DropdownMenuSeparator`, diakhiri "Reset filter"; tombol menampilkan badge jumlah filter aktif.
3. **Tabel:** kolom pertama `#` = nomor urut berlanjut antar halaman (`(halaman − 1) × per_halaman + indeks + 1`, rata kanan, `text-muted-foreground`); kolom aksi `⋯` di paling kanan. Empty state berbeda untuk "belum ada data" dan "tidak ada hasil pencarian/filter" (dengan tombol reset). Skeleton saat memuat. Tabel bergeser horizontal di layar sempit tanpa melebarkan halaman.
4. **Footer** (`border-t`): kiri "Menampilkan 11–20 dari 47 data" (atau "0 data"); kanan navigasi « ‹ [maks. 5 nomor, jendela bergeser] › » dengan aria-label & tooltip Bahasa Indonesia; tetap tampil (nonaktif) bila hanya satu halaman.

**Jarak horizontal seragam:** satu nilai tepi (`EDGE_X`/`EDGE_LEFT`/`EDGE_RIGHT` = `px-6`, di `components/data-table/layout.ts`) dipakai header card, sel pertama & terakhir tabel (header kolom dan baris), dan footer. Garis kiri: judul tabel = kolom `#` = info halaman. Garis kanan: tombol Filter = kolom aksi = tombol ». Sel lain memakai padding normal komponen `Table`. Jangan memakai `CardHeader`/`CardContent` di dalam card tabel (padding ganda).

**Konfigurasi per halaman:** `DataTableCard` hanya menerima `title`, `description?`, `table` (dari `useTableQuery`), `data` (paginate), `columns`, `rowKey`, `rowActions?`, `searchPlaceholder`, `filterGroups?`, `emptyState`. Halaman **tidak boleh** mengatur padding atau tata letak tabel sendiri.

**Perilaku:**
- Pencarian, filter, dan pagination diproses di **server** (`paginate()`), disimpan di query string (`?search=&{filter}[]=&page=`), dikirim lewat Inertia dengan `preserveState` + `preserveScroll`. Pencarian di-debounce ±300 ms; setiap perubahan pencarian/filter kembali ke halaman 1.
- Jumlah baris per halaman dari `config('fleet.per_page')` (default 10).

**Server (satu cara konsisten):**
- FormRequest daftar menyediakan `tableQuery(): TableQuery` dengan **whitelist** nilai filter per kunci (`App\Support\TableQuery::fromRequest`). Nilai tak dikenal **diabaikan**, bukan error.
- **Filter periode** (Riwayat, Log Aktivitas): `TableQuery::fromRequest(..., withPeriod: true)` membaca `?from=&to=` (Y-m-d, inklusif; tidak valid diabaikan, terbalik ditukar). Bila server mengirim `query.period`, header tabel otomatis menampilkan tombol **Periode** — tidak ada prop tambahan di `DataTableCard`.
- Grup filter dengan daftar panjang (mis. Mobil, Karyawan, Petugas) memakai `submenu: true` agar tampil sebagai submenu bergulir di dalam dropdown Filter.
- Model menyediakan scope `scopeForTable(Builder, TableQuery)` untuk pencarian & filter.
- Controller memakai trait `PaginatesForTable::paginateForTable()` dan mengirim `API Resource` yang field-nya dibatasi (bukan model utuh), plus `query` (`TableQuery::toArray()`) dan opsi filter (`Enum::options()` atau dari config).
- Test wajib per tabel: pencarian & filter mengembalikan data yang benar, parameter tidak valid diabaikan, dan nomor urut halaman 2 dimulai dari 11 (`meta.from`).

**Grup filter acuan:** Data Mobil — Status (Tersedia, Dipinjam, Maintenance, Nonaktif); Data Karyawan — Divisi (dari config), Status (Aktif, Nonaktif); Peminjaman — Status (Dijadwalkan, Aktif, Terlambat, Belum Diambil); Riwayat — Status, Keterlambatan, Divisi, Mobil, Karyawan + filter periode; Log Aktivitas — Jenis data, Petugas + filter periode.

---

## 9. Pengujian

- Setiap Action dan aturan domain di section 5 wajib punya feature test Pest, termasuk kasus gagal.
- Setiap endpoint diuji untuk: tamu (redirect login), Admin, Pengelola, dan akun nonaktif.
- Test unggah memakai `Storage::fake()` dan `UploadedFile::fake()->image()`.
- Test waktu memakai `$this->travelTo()`; jangan bergantung pada jam nyata.
- Opsi `FLEET_*` diuji untuk kedua nilai dengan mengubah config di dalam test (`config([...])`).
- Bug yang diperbaiki **wajib** disertai test yang gagal sebelum perbaikan.
- Daftar test minimal ada di PRD section 14.1.

---

## 10. Gaya Kode

- PHP: `declare(strict_types=1);` dan kelas `final` diterapkan ke file baru dan file yang disentuh, **kecuali** kelas yang perlu di-mock di test (jangan dibuat `final`). Type hint parameter & return di semua method, properti `readonly` bila memungkinkan.
- Mass assignment memakai atribut `#[Fillable([...])]` yang eksplisit; `$guarded = []` tetap dilarang.
- Penamaan: kelas `PascalCase`, method/variabel `camelCase`, Action berupa kata kerja (`ScheduleBooking`), enum case `PascalCase`.
- Tidak ada magic number/string: gunakan enum, konstanta, atau `config/fleet.php`.
- Komentar menjelaskan **mengapa**, bukan **apa**.
- Tidak ada kode mati, `dd()`, `dump()`, `console.log`, atau TODO tanpa konteks di commit.

---

## 11. Git & Alur Kerja Agent

1. Baca `prd.md` dan file terkait sebelum mengubah kode. Urutan fase dan prompt tiap fase ada di `PROMPTS.md` (8 fase, sama dengan PRD section 16).
2. Kerjakan perubahan kecil dan fokus; satu tujuan per commit.
3. Pesan commit format Conventional Commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`.
4. Sebelum menyatakan selesai, jalankan: `vendor/bin/pint`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run check`, `npm run types:check`, dan `npm run build` bila mengubah frontend. Semua harus lolos.
5. Jangan mengubah file di luar cakupan tugas, termasuk konfigurasi tooling, tanpa alasan.
6. Jika menemukan kebutuhan yang tidak tercakup PRD atau bertentangan dengan PRD, **tanyakan dulu**.

### Definition of Done
- [ ] Sesuai PRD dan aturan domain section 5
- [ ] Validasi di FormRequest, otorisasi di Policy
- [ ] Logika bisnis di Action, controller tetap tipis
- [ ] Test ditulis dan lolos (termasuk kasus gagal & hak akses)
- [ ] Pint, Larastan, `npm run check` (oxlint + oxfmt), `npm run types:check` lolos
- [ ] Tampil benar di light & dark mode, teks Bahasa Indonesia
- [ ] Aktivitas yang mengubah data tercatat di activity log
- [ ] Migration baru (bukan edit lama) bila skema berubah; `.env.example` diperbarui (dengan komentar) bila ada variabel baru

---

## 12. Larangan Ringkas

- ❌ Menambah login/registrasi karyawan, approval, odometer, tanda tangan, PDF, notifikasi eksternal
- ❌ Mengaktifkan kembali registrasi, lupa password lewat email, verifikasi email, atau passkey
- ❌ Memasang ESLint/Prettier, atau memuat font/aset dari CDN eksternal
- ❌ Logika bisnis di controller atau komponen React
- ❌ Hard delete data transaksi/master/akun/log
- ❌ `$guarded = []`, `$request->all()`, `DB::raw` dengan input pengguna, `dangerouslySetInnerHTML`
- ❌ Menyimpan file dengan nama asli atau di luar `Storage`
- ❌ Mengirim model utuh/kolom sensitif sebagai props Inertia
- ❌ Warna Tailwind langsung atau hex; mengedit `components/ui` tanpa alasan
- ❌ `env()` di luar `config/`, rahasia di kode, commit `.env`
- ❌ Mengedit migration lama
- ❌ Menambah dependensi tanpa persetujuan
