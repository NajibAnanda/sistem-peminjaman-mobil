<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status mobil yang tampil di aplikasi, diturunkan dengan prioritas
 * Nonaktif → Maintenance → Dipinjam → Tersedia (prd.md section 5.3).
 * Tidak pernah disimpan.
 */
enum CarDisplayStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Borrowed = 'borrowed';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';

    public static function resolve(bool $isActive, CarOperationalStatus $operationalStatus, bool $hasActiveBooking): self
    {
        return match (true) {
            ! $isActive => self::Inactive,
            $operationalStatus === CarOperationalStatus::Maintenance => self::Maintenance,
            $hasActiveBooking => self::Borrowed,
            default => self::Available,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Borrowed => 'Dipinjam',
            self::Maintenance => 'Maintenance',
            self::Inactive => 'Nonaktif',
        };
    }
}
