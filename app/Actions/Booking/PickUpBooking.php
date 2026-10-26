<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Booking\Concerns\StoresEvidencePhoto;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Enums\CarOperationalStatus;
use App\Enums\PhotoSource;
use App\Enums\PhotoStage;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use App\Support\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class PickUpBooking
{
    use StoresEvidencePhoto;

    public function __construct(private readonly ImageProcessor $images) {}

    /**
     * FR-15 & AGENTS.md 5.12: tepat 1 foto; boleh sebelum waktu mulai, tetapi
     * ditolak bila mobil Maintenance atau masih dipakai peminjaman lain.
     */
    public function handle(Booking $booking, UploadedFile $photo, PhotoSource $source, User $actor): Booking
    {
        return $this->withEvidencePhoto($photo, fn (string $path): Booking => DB::transaction(
            function () use ($booking, $source, $actor, $path): Booking {
                $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

                if (! $booking->status->canTransitionTo(BookingStatus::Active)) {
                    throw BookingException::invalidTransition($booking->status, BookingStatus::Active);
                }

                $car = Car::query()->lockForUpdate()->findOrFail($booking->car_id);
                $employee = Employee::query()->lockForUpdate()->findOrFail($booking->employee_id);

                // Cek ulang saat serah terima: data master bisa berubah sejak dijadwalkan.
                if (! $employee->is_active) {
                    throw BookingException::employeeInactiveAtPickup($employee->name);
                }

                if (! $car->is_active) {
                    throw BookingException::carInactiveAtPickup($car);
                }

                if ($car->operational_status === CarOperationalStatus::Maintenance) {
                    throw BookingException::carUnderMaintenance($car);
                }

                $stillInUse = $car->bookings()
                    ->where('status', BookingStatus::Active)
                    ->whereKeyNot($booking->id)
                    ->value('code');

                if (is_string($stillInUse)) {
                    throw BookingException::carStillInUse($car, $stillInUse);
                }

                $this->recordEvidencePhoto($booking, PhotoStage::Pickup, $source, $path, $actor);

                // start_at tetap sebagai rencana; waktu ambil aktual dicatat terpisah.
                $booking->forceFill([
                    'status' => BookingStatus::Active,
                    'picked_up_at' => now(),
                    'picked_up_by' => $actor->id,
                ])->save();

                activity(ActivityLogName::Booking)
                    ->performedOn($booking)
                    ->causedBy($actor)
                    ->event(ActivityEvent::PickedUp->value)
                    ->withChanges([
                        'old' => ['status' => BookingStatus::Scheduled->value],
                        'attributes' => ['status' => BookingStatus::Active->value, 'picked_up_at' => $booking->picked_up_at],
                    ])
                    ->withProperties(['photo_source' => $source->value])
                    ->log("Serah terima ambil {$booking->code} ({$car->plate_number})");

                return $booking;
            },
        ));
    }
}
