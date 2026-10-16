<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Booking\Concerns\StoresEvidencePhoto;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Enums\PhotoSource;
use App\Enums\PhotoStage;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\User;
use App\Support\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class ReturnBooking
{
    use StoresEvidencePhoto;

    public function __construct(private readonly ImageProcessor $images) {}

    /**
     * FR-16: tepat 1 foto dan catatan kondisi opsional; status menjadi Selesai.
     */
    public function handle(Booking $booking, UploadedFile $photo, PhotoSource $source, ?string $notes, User $actor): Booking
    {
        return $this->withEvidencePhoto($photo, fn (string $path): Booking => DB::transaction(
            function () use ($booking, $source, $notes, $actor, $path): Booking {
                $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

                if (! $booking->status->canTransitionTo(BookingStatus::Completed)) {
                    throw BookingException::invalidTransition($booking->status, BookingStatus::Completed);
                }

                $this->recordEvidencePhoto($booking, PhotoStage::Return, $source, $path, $actor);

                $booking->forceFill([
                    'status' => BookingStatus::Completed,
                    'returned_at' => now(),
                    'returned_by' => $actor->id,
                    'return_notes' => $notes,
                ])->save();

                activity(ActivityLogName::Booking)
                    ->performedOn($booking)
                    ->causedBy($actor)
                    ->event(ActivityEvent::Returned->value)
                    ->withChanges([
                        'old' => ['status' => BookingStatus::Active->value],
                        'attributes' => [
                            'status' => BookingStatus::Completed->value,
                            'returned_at' => $booking->returned_at,
                            'return_notes' => $notes,
                        ],
                    ])
                    ->withProperties([
                        'photo_source' => $source->value,
                        'late' => $booking->returned_at?->greaterThan($booking->planned_return_at) ?? false,
                    ])
                    ->log("Serah terima kembali {$booking->code}");

                return $booking;
            },
        ));
    }
}
