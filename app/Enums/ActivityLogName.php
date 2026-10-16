<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kelompok data pada log aktivitas (kolom `log_name`).
 */
enum ActivityLogName: string
{
    case Staff = 'staff';
    case Car = 'car';
    case Employee = 'employee';
    case Booking = 'booking';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Akun Petugas',
            self::Car => 'Data Mobil',
            self::Employee => 'Data Karyawan',
            self::Booking => 'Peminjaman',
        };
    }
}
