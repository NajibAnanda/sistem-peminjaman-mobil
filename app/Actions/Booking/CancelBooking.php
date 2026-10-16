<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CancelBooking
{
    /**
     * FR-14: hanya peminjaman Dijadwalkan; alasan wajib (divalidasi di request).
     */
    public function handle(Booking $booking, string $reason, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $reason, $actor): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $booking->status->canTransitionTo(BookingStatus::Cancelled)) {
                throw BookingException::invalidTransition($booking->status, BookingStatus::Cancelled);
            }

            $booking->forceFill([
                'status' => BookingStatus::Cancelled,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
            ])->save();

            activity(ActivityLogName::Booking)
                ->performedOn($booking)
                ->causedBy($actor)
                ->event(ActivityEvent::Cancelled->value)
                ->withChanges([
                    'old' => ['status' => BookingStatus::Scheduled->value],
                    'attributes' => ['status' => BookingStatus::Cancelled->value, 'cancellation_reason' => $reason],
                ])
                ->log("Membatalkan peminjaman {$booking->code}");

            return $booking;
        });
    }
}
