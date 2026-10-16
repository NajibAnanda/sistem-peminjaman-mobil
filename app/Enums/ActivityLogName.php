<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kelompok data pada log aktivitas (kolom `log_name`).
 */
enum ActivityLogName: string
{
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Akun Petugas',
        };
    }
}
