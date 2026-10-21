<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Batas durasi satu peminjaman (config/fleet.php), dari waktu mulai sampai
 * rencana kembali. Berlaku untuk jadwal baru, edit jadwal, dan perpanjangan.
 * Durasi minimal selalu berlaku; durasi maksimal hanya bila diisi di .env
 * (FLEET_MAX_BOOKING_DAYS), selain itu tanpa batas.
 */
final class BookingDuration
{
    private const MINUTES_PER_DAY = 1440;

    /**
     * Pesan kesalahan bila durasi di luar batas, atau null bila sesuai.
     * Rentang terbalik/kosong dibiarkan untuk aturan `after`.
     */
    public static function violation(CarbonInterface $start, CarbonInterface $end): ?string
    {
        if ($end->lessThanOrEqualTo($start)) {
            return null;
        }

        $minutes = $start->diffInMinutes($end);
        $minMinutes = self::minMinutes();
        $maxDays = self::maxDays();

        if ($minutes < $minMinutes) {
            return "Durasi peminjaman minimal {$minMinutes} menit.";
        }

        if ($maxDays !== null && $minutes > $maxDays * self::MINUTES_PER_DAY) {
            return "Durasi peminjaman maksimal {$maxDays} hari sejak waktu mulai.";
        }

        return null;
    }

    public static function minMinutes(): int
    {
        return (int) config('fleet.booking.min_duration_minutes');
    }

    /**
     * null = tanpa batas maksimal.
     */
    public static function maxDays(): ?int
    {
        $days = config('fleet.booking.max_duration_days');

        return is_numeric($days) ? (int) $days : null;
    }
}
