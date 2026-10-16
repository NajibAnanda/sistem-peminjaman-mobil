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

    private const MINUTES_PER_HOUR = 60;

    private const HOURS_PER_DAY = 24;

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

    /**
     * Lama waktu dalam menit, sama dengan formatDuration() di frontend:
     * "2 hari 3 jam", "3 jam 15 menit", atau "15 menit".
     */
    public static function duration(int $minutes): string
    {
        $minutes = max(1, $minutes);
        $totalHours = intdiv($minutes, self::MINUTES_PER_HOUR);
        $days = intdiv($totalHours, self::HOURS_PER_DAY);
        $hours = $totalHours % self::HOURS_PER_DAY;
        $rest = $minutes % self::MINUTES_PER_HOUR;

        return match (true) {
            $days > 0 => $hours > 0 ? "{$days} hari {$hours} jam" : "{$days} hari",
            $totalHours > 0 => $rest > 0 ? "{$totalHours} jam {$rest} menit" : "{$totalHours} jam",
            default => "{$rest} menit",
        };
    }
}
