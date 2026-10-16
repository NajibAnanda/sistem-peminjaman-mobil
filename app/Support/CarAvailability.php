<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CarOperationalStatus;
use App\Models\Booking;
use App\Models\Car;
use Carbon\CarbonInterface;

/**
 * Daftar mobil untuk form jadwal beserta ketersediaannya pada rentang waktu
 * yang dipilih (FR-11). Hanya penanda di antarmuka; keputusan akhir tetap di
 * Action ScheduleBooking/UpdateBookingSchedule yang mengunci baris mobil.
 */
final class CarAvailability
{
    /**
     * @return list<array{id: int, plate_number: string, brand: string, model: string, available: bool|null, reason: string|null}>
     */
    public static function forForm(?CarbonInterface $start, ?CarbonInterface $end, ?Booking $except = null): array
    {
        $cars = Car::query()
            ->where('is_active', true)
            ->when($except, fn ($query, Booking $booking) => $query->orWhere('id', $booking->car_id))
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'brand', 'model', 'operational_status', 'is_active']);

        // Satu query untuk semua bentrok pada rentang ini, dikelompokkan per mobil.
        $conflicts = $start === null || $end === null
            ? collect()
            : Booking::query()
                ->overlapping($start, $end)
                ->when($except, fn ($query, Booking $booking) => $query->whereKeyNot($booking->id))
                ->orderBy('start_at')
                ->get(['id', 'car_id', 'code'])
                ->groupBy('car_id');

        return array_values($cars->map(function (Car $car) use ($conflicts, $start): array {
            $reason = match (true) {
                ! $car->is_active => 'Nonaktif',
                $car->operational_status === CarOperationalStatus::Maintenance => 'Maintenance',
                $conflicts->has($car->id) => 'Bentrok dengan '.$conflicts->get($car->id)->pluck('code')->implode(', '),
                default => null,
            };

            return [
                'id' => $car->id,
                'plate_number' => $car->plate_number,
                'brand' => $car->brand,
                'model' => $car->model,
                // null = rentang waktu belum dipilih sehingga bentrok belum bisa dicek.
                'available' => $reason !== null ? false : ($start === null ? null : true),
                'reason' => $reason,
            ];
        })->all());
    }
}
