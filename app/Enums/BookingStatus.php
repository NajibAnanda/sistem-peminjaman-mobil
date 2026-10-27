<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status peminjaman yang disimpan (prd.md section 5.1).
 */
enum BookingStatus: string
{
    use HasOptions;

    case Scheduled = 'scheduled';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Status yang masih "berjalan": memblokir penonaktifan mobil/pegawai
     * dan diperhitungkan dalam cek bentrok.
     *
     * @return list<self>
     */
    public static function ongoing(): array
    {
        return [self::Scheduled, self::Active];
    }

    /**
     * Satu-satunya transisi yang sah (AGENTS.md 5.1); selain ini ditolak.
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Scheduled => in_array($next, [self::Active, self::Cancelled], true),
            self::Active => $next === self::Completed,
            self::Completed, self::Cancelled => false,
        };
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
