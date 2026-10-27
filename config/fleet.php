<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Divisi Pegawai
    |--------------------------------------------------------------------------
    |
    | Pilihan divisi yang valid untuk data pegawai. Untuk menambah divisi,
    | cukup tambahkan nama baru ke daftar ini.
    |
    */

    'divisions' => [
        'Keuangan',
        'SDM',
        'Teknik',
    ],

    /*
    |--------------------------------------------------------------------------
    | Foto
    |--------------------------------------------------------------------------
    |
    | Batas ukuran unggah (kilobyte, mengikuti aturan validasi `max`) dan sisi
    | terpanjang gambar setelah diproses ulang di server.
    |
    */

    'photos' => [
        'max_upload_kb' => 5120,
        'max_dimension_px' => 1600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Polling
    |--------------------------------------------------------------------------
    |
    | Interval pembaruan badge Terlambat dan Belum Diambil di menu.
    |
    */

    'polling_interval_seconds' => 60,

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Jumlah baris per halaman untuk daftar data (pagination di server).
    |
    */

    'per_page' => 10,

    /*
    |--------------------------------------------------------------------------
    | Peminjaman
    |--------------------------------------------------------------------------
    |
    | Toleransi waktu mulai di masa lalu (untuk pegawai yang datang langsung
    | saat jadwal dicatat), durasi minimal satu peminjaman, serta panjang
    | minimal tujuan dan alasan pembatalan/perpanjangan.
    |
    | Durasi dihitung dari waktu mulai sampai rencana kembali (termasuk
    | setelah perpanjangan). Tidak ada batas maksimal kecuali
    | FLEET_MAX_BOOKING_DAYS diisi. Durasi di atas
    | FLEET_LONG_BOOKING_WARNING_DAYS hanya memunculkan peringatan konfirmasi
    | di form (tidak memblokir penyimpanan).
    |
    */

    'booking' => [
        'start_tolerance_minutes' => 15,
        'min_duration_minutes' => 30,
        'max_duration_days' => is_numeric(env('FLEET_MAX_BOOKING_DAYS')) ? (int) env('FLEET_MAX_BOOKING_DAYS') : null,
        'long_booking_warning_days' => (int) env('FLEET_LONG_BOOKING_WARNING_DAYS', 7),
        'purpose_min_length' => 5,
        'reason_min_length' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemilih Tanggal & Waktu
    |--------------------------------------------------------------------------
    |
    | Kelipatan menit pada pilihan jam di form (mis. 5 = 00, 05, 10, ... 55).
    | Hanya membatasi pilihan di antarmuka; validasi waktu tetap di server.
    |
    */

    'date_picker' => [
        'minute_step' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ekspor Laporan
    |--------------------------------------------------------------------------
    |
    | Pemisah kolom CSV mengikuti pemisah daftar di pengaturan Region Windows
    | komputer yang membuka file ("," English, ";" Indonesia). File CSV diberi
    | BOM UTF-8 agar huruf non-ASCII terbaca benar di Excel.
    |
    | Ekspor wajib memilih periode dengan rentang paling panjang
    | `max_range_days` hari, agar file tetap ringan dibuka.
    |
    */

    'export' => [
        'csv_delimiter' => env('FLEET_CSV_DELIMITER', ','),
        'chunk_size' => 500,
        'max_range_days' => 366,
    ],

    /*
    |--------------------------------------------------------------------------
    | Keamanan
    |--------------------------------------------------------------------------
    |
    | Kedua opsi disarankan `true` bila sistem dapat diakses dari internet.
    | Cek password bocor membutuhkan koneksi internet dari server.
    |
    */

    'security' => [
        'require_two_factor_for_manager' => (bool) env('FLEET_REQUIRE_2FA_FOR_MANAGER', false),
        'password_check_uncompromised' => (bool) env('FLEET_PASSWORD_CHECK_UNCOMPROMISED', false),
        'password_min_length' => 10,
        'temporary_password_length' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Akun Pengelola Pertama
    |--------------------------------------------------------------------------
    |
    | Dipakai seeder untuk membuat akun Pengelola pertama. Password ini wajib
    | diganti saat login pertama.
    |
    */

    'initial_manager' => [
        'name' => env('FLEET_MANAGER_NAME', 'Pengelola'),
        'email' => env('FLEET_MANAGER_EMAIL'),
        'password' => env('FLEET_MANAGER_PASSWORD'),
    ],

];
