<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Divisi Karyawan
    |--------------------------------------------------------------------------
    |
    | Pilihan divisi yang valid untuk data karyawan. Untuk menambah divisi,
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
    | Peminjaman
    |--------------------------------------------------------------------------
    |
    | Toleransi waktu mulai di masa lalu, untuk karyawan yang datang langsung
    | saat jadwal dicatat.
    |
    */

    'booking' => [
        'start_tolerance_minutes' => 15,
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
