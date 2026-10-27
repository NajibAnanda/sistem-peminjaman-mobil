<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Booking\Concerns\ChecksBookingAvailability;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use App\Support\BookingCodeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ScheduleBooking
{
    use ChecksBookingAvailability;

    public function __construct(private readonly BookingCodeGenerator $codes) {}

    /**
     * @param  array{employee_id: int, car_id: int, start_at: CarbonImmutable, planned_return_at: CarbonImmutable, purpose: string}  $data
     */
    public function handle(array $data, User $actor): Booking
    {
        return DB::transaction(function () use ($data, $actor): Booking {
            // Kunci mobil dulu: dua petugas yang menjadwalkan mobil yang sama
            // bersamaan diproses bergantian, sehingga hanya satu yang lolos
            // cek bentrok (FR-12). Pegawai dikunci agar tidak dinonaktifkan
            // di antara pengecekan dan penyimpanan.
            $car = Car::query()->lockForUpdate()->findOrFail($data['car_id']);
            $employee = Employee::query()->lockForUpdate()->findOrFail($data['employee_id']);

            $this->ensureCarSchedulable($car);
            $this->ensureEmployeeActive($employee);
            $this->ensureNoConflict($car, $data['start_at'], $data['planned_return_at']);
            $this->ensureEmployeeFree($employee, $data['start_at'], $data['planned_return_at']);

            $booking = Booking::query()->create([
                'code' => $this->codes->next(),
                'employee_id' => $employee->id,
                'car_id' => $car->id,
                'purpose' => $data['purpose'],
                'start_at' => $data['start_at'],
                'planned_return_at' => $data['planned_return_at'],
                'status' => BookingStatus::Scheduled,
                'created_by' => $actor->id,
            ]);

            activity(ActivityLogName::Booking)
                ->performedOn($booking)
                ->causedBy($actor)
                ->event(ActivityEvent::Created->value)
                ->withChanges(['attributes' => $booking->only(['code', 'employee_id', 'car_id', 'purpose', 'start_at', 'planned_return_at'])])
                ->log("Menjadwalkan peminjaman {$booking->code} ({$car->brand} {$car->model} ({$car->plate_number}), {$employee->name})");

            return $booking;
        });
    }
}
