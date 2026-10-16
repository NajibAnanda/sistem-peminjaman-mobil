<?php

declare(strict_types=1);

namespace App\Actions\Booking\Concerns;

use App\Enums\CarOperationalStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use Carbon\CarbonInterface;

/**
 * Pengecekan bersama untuk penjadwalan, edit jadwal, dan perpanjangan.
 * Pemanggil wajib sudah mengunci baris mobil (lockForUpdate) di dalam
 * transaksi agar hasil pengecekan tetap berlaku sampai data disimpan.
 */
trait ChecksBookingAvailability
{
    private function ensureCarSchedulable(Car $car): void
    {
        if (! $car->is_active) {
            throw BookingException::carNotSchedulable($car->plate_number, 'nonaktif');
        }

        if ($car->operational_status === CarOperationalStatus::Maintenance) {
            throw BookingException::carNotSchedulable($car->plate_number, 'Maintenance');
        }
    }

    private function ensureEmployeeActive(Employee $employee): void
    {
        if (! $employee->is_active) {
            throw BookingException::employeeInactive($employee->name);
        }
    }

    private function ensureNoConflict(Car $car, CarbonInterface $start, CarbonInterface $end, ?Booking $except = null): void
    {
        $conflicts = Booking::query()
            ->with('employee:id,name')
            ->where('car_id', $car->id)
            ->overlapping($start, $end)
            ->when($except, fn ($query, Booking $booking) => $query->whereKeyNot($booking->id))
            ->orderBy('start_at')
            ->get();

        if ($conflicts->isNotEmpty()) {
            throw BookingException::conflict($conflicts);
        }
    }
}
