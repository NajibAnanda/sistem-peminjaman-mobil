<?php

declare(strict_types=1);

namespace App\Actions\Car;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Enums\CarOperationalStatus;
use App\Exceptions\CarException;
use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SetCarOperationalStatus
{
    /**
     * FR-07: mobil yang sedang dipinjam tidak dapat diubah ke Maintenance.
     * Jadwal mendatang tidak menghalangi; antarmuka hanya memperingatkan.
     */
    public function handle(Car $car, CarOperationalStatus $status, User $actor): Car
    {
        return DB::transaction(function () use ($car, $status, $actor): Car {
            $car = Car::query()->lockForUpdate()->findOrFail($car->id);

            if (! $car->is_active) {
                throw CarException::inactiveCannotChangeStatus();
            }

            $previous = $car->operational_status;

            if ($previous === $status) {
                return $car;
            }

            if ($status === CarOperationalStatus::Maintenance) {
                $activeBookingCode = $car->bookings()
                    ->where('status', BookingStatus::Active)
                    ->value('code');

                if (is_string($activeBookingCode)) {
                    throw CarException::borrowedCannotEnterMaintenance($activeBookingCode);
                }
            }

            $car->forceFill(['operational_status' => $status])->save();

            activity(ActivityLogName::Car)
                ->performedOn($car)
                ->causedBy($actor)
                ->event(ActivityEvent::OperationalStatusChanged->value)
                ->withChanges([
                    'old' => ['operational_status' => $previous->value],
                    'attributes' => ['operational_status' => $status->value],
                ])
                ->log("Mengubah status mobil {$car->brand} {$car->model} ({$car->plate_number}) menjadi {$status->label()}");

            return $car;
        });
    }
}
