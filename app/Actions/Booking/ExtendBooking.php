<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Booking\Concerns\ChecksBookingAvailability;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ExtendBooking
{
    use ChecksBookingAvailability;

    /**
     * FR-18 & AGENTS.md 5.14: hanya peminjaman Aktif, rencana kembali baru
     * wajib lebih lambat (tidak boleh memperpendek), dicek bentrok, dan setiap
     * perpanjangan disimpan sebagai riwayat.
     */
    public function handle(Booking $booking, CarbonImmutable $newPlannedReturn, string $reason, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $newPlannedReturn, $reason, $actor): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status !== BookingStatus::Active) {
                throw BookingException::notExtendable($booking->status);
            }

            $previous = $booking->planned_return_at;

            if ($newPlannedReturn->lessThanOrEqualTo($previous)) {
                throw BookingException::extensionNotLater($previous);
            }

            $car = Car::query()->lockForUpdate()->findOrFail($booking->car_id);

            $employee = Employee::query()->lockForUpdate()->findOrFail($booking->employee_id);

            try {
                $this->ensureNoConflict($car, $booking->start_at, $newPlannedReturn, $booking);
                $this->ensureEmployeeFree($employee, $booking->start_at, $newPlannedReturn, $booking);
            } catch (BookingException $exception) {
                // Pada perpanjangan, yang perlu diubah adalah waktu kembali, bukan mobil.
                throw $exception->forField('new_planned_return_at');
            }

            BookingExtension::query()->create([
                'booking_id' => $booking->id,
                'previous_planned_return_at' => $previous,
                'new_planned_return_at' => $newPlannedReturn,
                'reason' => $reason,
                'extended_by' => $actor->id,
            ]);

            $booking->forceFill(['planned_return_at' => $newPlannedReturn])->save();

            activity(ActivityLogName::Booking)
                ->performedOn($booking)
                ->causedBy($actor)
                ->event(ActivityEvent::Extended->value)
                ->withChanges([
                    'old' => ['planned_return_at' => $previous],
                    'attributes' => ['planned_return_at' => $newPlannedReturn],
                ])
                ->withProperties(['reason' => $reason])
                ->log("Memperpanjang peminjaman {$booking->code}");

            return $booking;
        });
    }
}
