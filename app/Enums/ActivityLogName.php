<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Kelompok data pada log aktivitas (kolom `log_name`).
 */
enum ActivityLogName: string
{
    use HasOptions;

    case Staff = 'staff';
    case Car = 'car';
    case Employee = 'employee';
    case Booking = 'booking';
    case Report = 'report';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Akun Petugas',
            self::Car => 'Data Mobil',
            self::Employee => 'Data Pegawai',
            self::Booking => 'Peminjaman',
            self::Report => 'Laporan',
        };
    }
}
