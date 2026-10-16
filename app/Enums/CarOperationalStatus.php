<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Kondisi operasional yang disimpan di `cars.operational_status`.
 * "Dipinjam" sengaja tidak ada di sini (diturunkan dari peminjaman aktif).
 */
enum CarOperationalStatus: string
{
    use HasOptions;

    case Operational = 'operational';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operasional',
            self::Maintenance => 'Maintenance',
        };
    }
}
