<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Format tanggal Indonesia di sisi server (pesan error, laporan), sama
 * dengan helper frontend resources/js/lib/datetime.ts: "16 Okt 2026, 14.30".
 */
final class DateFormat
{
    private const LOCALE = 'id';

    public static function dateTime(CarbonInterface $value): string
    {
        return $value->copy()
            ->setTimezone((string) config('app.timezone'))
            ->locale(self::LOCALE)
            ->translatedFormat('j M Y, H.i');
    }

    public static function range(CarbonInterface $start, CarbonInterface $end): string
    {
        return self::dateTime($start).' – '.self::dateTime($end);
    }
}
