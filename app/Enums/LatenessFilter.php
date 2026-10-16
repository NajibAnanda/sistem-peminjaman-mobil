<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Filter keterlambatan di Riwayat (FR-25). Dihitung dari waktu, tidak disimpan.
 */
enum LatenessFilter: string
{
    use HasOptions;

    /** Selesai setelah rencana kembali, atau masih Aktif dan sudah Terlambat. */
    case Late = 'late';

    /** Selesai paling lambat pada rencana kembali. */
    case OnTime = 'on_time';

    public function label(): string
    {
        return match ($this) {
            self::Late => 'Terlambat kembali',
            self::OnTime => 'Tepat waktu',
        };
    }
}
