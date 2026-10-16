<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Booking;
use Carbon\CarbonInterface;

/**
 * Jumlah Terlambat & Belum Diambil untuk badge menu (FR-21, FR-22). Dua COUNT
 * yang masing-masing tercakup index (status, planned_return_at) dan
 * (status, start_at), sehingga murah walau dipanggil setiap polling.
 */
final class BookingAlerts
{
    /**
     * @return array{overdue: int, not_picked_up: int}
     */
    public static function counts(?CarbonInterface $now = null): array
    {
        $now ??= now();

        return [
            'overdue' => Booking::query()->overdue($now)->count(),
            'not_picked_up' => Booking::query()->notPickedUp($now)->count(),
        ];
    }
}
