<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Booking\Concerns\ChecksBookingAvailability;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class UpdateBookingSchedule
{
    use ChecksBookingAvailability;

    private const LOGGED_ATTRIBUTES = ['employee_id', 'car_id', 'purpose', 'start_at', 'planned_return_at'];

    /**
     * AGENTS.md 5.13: hanya saat Dijadwalkan; boleh mengganti mobil, pegawai,
     * waktu, dan tujuan. Cek bentrok dijalankan ulang bila mobil atau waktu
     * berubah. (Toleransi waktu mulai divalidasi di UpdateBookingRequest.)
     *
     * @param  array{employee_id: int, car_id: int, start_at: CarbonImmutable, planned_return_at: CarbonImmutable, purpose: string}  $data
     */
    public function handle(Booking $booking, array $data, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $data, $actor): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status !== BookingStatus::Scheduled) {
                throw BookingException::notEditable($booking->status);
            }

            // Kunci mobil lama & baru dengan urutan id tetap agar tidak deadlock.
            $cars = Car::query()
                ->whereKey(array_unique([$booking->car_id, $data['car_id']]))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $car = $cars->get($data['car_id']) ?? throw (new ModelNotFoundException)->setModel(Car::class);

            $carChanged = $booking->car_id !== $car->id;
            $timeChanged = ! $booking->start_at->equalTo($data['start_at'])
                || ! $booking->planned_return_at->equalTo($data['planned_return_at']);

            if ($carChanged) {
                $this->ensureCarSchedulable($car);
            }

            $employeeChanged = $booking->employee_id !== $data['employee_id'];
            $employee = Employee::query()->lockForUpdate()->findOrFail($data['employee_id']);

            if ($employeeChanged) {
                $this->ensureEmployeeActive($employee);
            }

            if ($carChanged || $timeChanged) {
                $this->ensureNoConflict($car, $data['start_at'], $data['planned_return_at'], $booking);
            }

            if ($employeeChanged || $timeChanged) {
                $this->ensureEmployeeFree($employee, $data['start_at'], $data['planned_return_at'], $booking);
            }

            $before = $booking->only(self::LOGGED_ATTRIBUTES);
            $booking->fill($data);
            $changed = array_keys($booking->getDirty());
            $booking->save();

            if ($changed !== []) {
                activity(ActivityLogName::Booking)
                    ->performedOn($booking)
                    ->causedBy($actor)
                    ->event(ActivityEvent::Updated->value)
                    ->withChanges([
                        'old' => array_intersect_key($before, array_flip($changed)),
                        'attributes' => $booking->only($changed),
                    ])
                    ->log("Mengubah jadwal peminjaman {$booking->code}");
            }

            return $booking;
        });
    }
}
