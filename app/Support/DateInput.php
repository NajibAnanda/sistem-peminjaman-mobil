<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Membaca input tanggal/waktu dari form. Pemilih tanggal mengirim ISO 8601
 * dengan offset zona waktu (mis. "2026-10-16T14:30:00+07:00"); nilai tanpa
 * offset dibaca dalam zona waktu aplikasi. Hasil selalu dikonversi ke zona
 * waktu aplikasi, karena Eloquent menyimpan jam apa adanya tanpa offset:
 * "07:30Z" yang tidak dikonversi akan tersimpan sebagai 07.30 WIB.
 */
final class DateInput
{
    public static function parse(string $value): CarbonImmutable
    {
        $timezone = (string) config('app.timezone');

        return CarbonImmutable::parse($value, $timezone)->setTimezone($timezone)->startOfMinute();
    }
}
