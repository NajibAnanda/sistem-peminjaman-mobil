<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Nilai filter status aktif/nonaktif pada tabel (akun petugas, karyawan).
 * Bukan kolom tersimpan: data menyimpan boolean `is_active`.
 */
enum ActiveStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
