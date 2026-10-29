# Panduan Pengguna Sistem Peminjaman Mobil Dinas

Panduan praktis operasional harian untuk petugas **General Affairs (GA)**.

---

## 1. Pengenalan & Peran Petugas

Aplikasi ini digunakan khusus oleh petugas internal GA untuk mencatat, menjadwalkan, dan memantau peminjaman armada mobil dinas kantor. **Pegawai peminjam tidak memiliki akun login**; data pegawai dicatat dan dipilih oleh petugas GA.

Sistem memiliki dua peran (_role_) pengguna:

1. **Admin (Petugas Operasional GA):**
    - Menjadwalkan peminjaman armada.
    - Melakukan serah terima ambil (mobil keluar kantor) dengan 1 foto bukti.
    - Melakukan serah terima kembali (mobil masuk kantor) dengan 1 foto bukti.
    - Mengubah jadwal, membatalkan, atau memperpanjang peminjaman.
    - Mengelola master Data Mobil dan Data Pegawai (termasuk tambah/hapus divisi).
    - Melihat dashboard jadwal dan mengekspor riwayat ke Excel / CSV.
2. **Pengelola (Manager GA):**
    - Memiliki semua hak akses Admin di atas.
    - **Fitur Khusus Pengelola:** Mengelola akun petugas (tambah petugas, nonaktifkan akun, reset password petugas), serta melihat catatan Log Aktivitas sistem.

---

## 2. Alur Utama Siklus Peminjaman

Alur peminjaman mobil berjalan dalam 3 status inti:

```
[Dijadwalkan]  --->  (Ambil: Wajib 1 Foto)  --->  [Aktif]  --->  (Kembali: Wajib 1 Foto)  --->  [Selesai]
      |
      +------------>  (Batal: Wajib Alasan) --->  [Dibatalkan]
```

### A. Membuat Jadwal Baru

1. Buka menu **Peminjaman** lalu klik tombol **+ Buat Jadwal**, atau langsung klik tombol **+ Peminjaman** di Dashboard.
2. Isi formulir peminjaman:
    - **Pegawai Peminjam:** Pilih nama pegawai peminjam dari daftar (dapat diketik untuk mencari).
    - **Mobil Dinas:** Pilih mobil yang tersedia. Mobil yang sedang dalam status _Maintenance_ tidak dapat dipilih.
    - **Waktu Mulai & Rencana Kembali:** Pilih tanggal dan jam. Durasi minimal adalah 30 menit.
    - **Tujuan Peminjaman:** Tuliskan keperluan dinas secara jelas (minimal 5 karakter).
3. Klik **Simpan Jadwal**. Sistem otomatis memeriksa bentrok jadwal mobil dan pegawai. Jika ada jadwal lain yang bertabrakan, sistem akan menolak dan memberi tahu jam bentroknya.

### B. Serah Terima Ambil (Mobil Diambil)

1. Buka menu **Peminjaman** atau cari kode peminjaman di pencarian atas (`Ctrl + K`).
2. Klik tombol **Ambil Mobil** pada kartu atau detail peminjaman.
3. Ambil atau unggah **tepat 1 foto bukti penyerahan**:
    - Dapat menggunakan kamera laptop/PC secara langsung, atau unggah file foto dari galeri.
    - Foto otomatis dikompresi agar hemat ruang penyimpanan.
4. Klik **Konfirmasi Serah Terima Ambil**. Status peminjaman berubah menjadi **Aktif**.

> **Catatan:** Serah terima ambil dapat dilakukan lebih awal dari jadwal rencana mulai, namun akan ditolak jika mobil masih digunakan oleh peminjam sebelumnya yang belum dikembalikan.

### C. Serah Terima Kembali (Mobil Dikembalikan)

1. Pada peminjaman berstatus **Aktif**, klik tombol **Kembalikan Mobil**.
2. Ambil atau unggah **tepat 1 foto bukti pengembalian** (kondisi mobil saat kembali).
3. Klik **Konfirmasi Pengembalian**. Status peminjaman berubah menjadi **Selesai**.

---

## 3. Modifikasi Peminjaman

### A. Mengubah Jadwal (Hanya untuk Status "Dijadwalkan")

- Buka detail peminjaman, lalu klik **Edit Jadwal**.
- Anda dapat mengganti mobil, pegawai, waktu rencana, maupun tujuan.
- Sistem akan menjalankan ulang pengecekan bentrok jadwal.

### B. Membatalkan Peminjaman (Hanya untuk Status "Dijadwalkan")

- Pada peminjaman yang batal digunakan, klik tombol **Batalkan**.
- Tuliskan alasan pembatalan dengan jelas (minimal 10 karakter).
- Status berubah menjadi **Dibatalkan** dan mobil dapat dipinjam oleh jadwal lain.

### C. Memperpanjang Waktu (Hanya untuk Status "Aktif")

- Jika pegawai menghubungi bahwa pemakaian mobil perlu diperpanjang, klik tombol **Perpanjang**.
- Tentukan waktu kembali baru (wajib lebih lama dari rencana kembali semula).
- Tuliskan alasan perpanjangan dinas (minimal 10 karakter).
- Sistem akan memverifikasi bahwa waktu perpanjangan tidak menabrak jadwal berikutnya dari mobil tersebut.

---

## 4. Penanganan Status Khusus

1. **Belum Diambil (Lencana Kuning):**
    - Muncul otomatis jika waktu mulai jadwal sudah lewat, namun petugas belum memproses serah terima ambil.
    - Petugas tetap dapat memproses serah terima ambil begitu pegawai datang mengambil kunci.
2. **Terlambat (Lencana Merah):**
    - Muncul otomatis jika mobil masih berstatus **Aktif** melebihi waktu rencana kembali yang dijadwalkan.
    - **Perhatian:** Mobil yang sedang berstatus Terlambat akan menahan serah terima ambil untuk jadwal peminjam berikutnya sampai mobil tersebut benar-benar telah dikembalikan.

---

## 5. Pengelolaan Data Master

### A. Data Mobil

- Buka menu **Mobil**.
- **Tambah Mobil Baru:** Masukkan plat nomor (format otomatis dirapikan), merek, model, tahun, dan kapasitas kursi.
- **Status Operasional:**
    - _Operasional_: Mobil siap pakai untuk dinas.
    - _Maintenance_: Mobil sedang servis/perbaikan dan otomatis dinonaktifkan dari pilihan jadwal baru.
- **Foto Mobil:** Anda dapat mengunggah hingga 2 foto referensi untuk setiap mobil. Slot kosong menampilkan label "Foto belum lengkap".
- Mobil yang sedang memiliki peminjaman aktif atau terjadwal **tidak dapat dinonaktifkan**.

### B. Data Pegawai & Divisi

- Buka menu **Pegawai**.
- **Tambah Pegawai:** Masukkan NIP, Nama Lengkap, Divisi, dan Nomor Telepon (format otomatis disimpan `08xx`).
- **Kelola Divisi:**
    - Saat menambah atau mengedit pegawai, Anda dapat mengetik nama divisi baru untuk menambahkannya ke sistem.
    - Divisi yang tidak lagi digunakan dapat dihapus melalui dialog pilihan divisi (hanya divisi yang tidak memiliki pegawai yang dapat dihapus).

---

## 6. Fitur Khusus Pengelola (Manager)

### A. Kelola Petugas GA

- Buka menu **Petugas**.
- **Tambah Petugas:** Masukkan nama, email kantor, dan pilih role (Admin atau Pengelola). Sistem akan memberikan **password sementara**.
- **Reset Password Petugas:** Jika petugas lupa password, Pengelola dapat menekan tombol **Reset Password**. Sistem akan membuatkan password sementara baru dan petugas wajib menggantinya saat login berikutnya.
- **Nonaktifkan Akun:** Petugas yang mutasi atau pindah unit kerja dapat dinonaktifkan agar tidak bisa login lagi.

### B. Ekspor Riwayat & Laporan

- Buka menu **Riwayat**.
- Atur filter periode (default bulan berjalan), status, divisi, mobil, atau keterlambatan.
- Klik tombol **Ekspor** dan pilih format **Excel (.xlsx)** atau **CSV**.
- File unduhan otomatis memuat data persis sesuai filter yang sedang aktif, lengkap dengan format tanggal Indonesia dan aman dibuka di Microsoft Excel.

### C. Log Aktivitas

- Buka menu **Log Aktivitas**.
- Menampilkan rekaman jejak audit lengkap: siapa petugas yang mengubah data, waktu perubahan, dan nilai data sebelum serta sesudahnya.

---

## 7. Tips & Keamanan Akun

1. **Ganti Password Sementara:** Saat pertama kali login dengan akun baru atau setelah direset oleh Pengelola, Anda akan diarahkan ke halaman wajib ganti password. Gunakan password minimal 10 karakter (kombinasi huruf dan angka).
2. **Autentikasi Dua Faktor (2FA):** Untuk keamanan ekstra, aktifkan 2FA di menu **Pengaturan > Keamanan** menggunakan aplikasi autentikator di ponsel (seperti Google Authenticator).
3. **Selalu Keluar (Logout) Saat Selesai Bertugas:** Klik profil Anda di sudut kiri bawah menu, pilih **Keluar**, dan konfirmasi dialog untuk mengakhiri sesi.
