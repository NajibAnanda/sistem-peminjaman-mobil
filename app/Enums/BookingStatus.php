<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status peminjaman yang disimpan (prd.md section 5.1). Aturan transisi
 * (canTransitionTo) ditambahkan bersama logika peminjaman di Fase 4.
 */
enum BookingStatus: string
{
    use HasOptions;

    case Scheduled = 'scheduled';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Status yang masih "berjalan": memblokir penonaktifan mobil/karyawan
     * dan diperhitungkan dalam cek bentrok.
     *
     * @return list<self>
     */
    public static function ongoing(): array
    {
        return [self::Scheduled, self::Active];
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Dijadwalkan',
            self::Active => 'Aktif',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
