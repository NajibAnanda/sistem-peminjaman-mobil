<?php

declare(strict_types=1);

namespace App\Actions\Car;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Exceptions\CarException;
use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeactivateCar
{
    public function handle(Car $car, User $actor): Car
    {
        return DB::transaction(function () use ($car, $actor): Car {
            // Baris mobil dikunci agar tidak ada jadwal baru yang lolos di
            // antara pengecekan dan penonaktifan.
            $car = Car::query()->lockForUpdate()->findOrFail($car->id);

            if (! $car->is_active) {
                throw CarException::alreadyInactive();
            }

            /** @var list<string> $blockingCodes */
            $blockingCodes = $car->bookings()->ongoing()->orderBy('start_at')->pluck('code')->all();

            if ($blockingCodes !== []) {
                throw CarException::hasOngoingBookings($blockingCodes);
            }

            $car->forceFill(['is_active' => false])->save();

            activity(ActivityLogName::Car)
                ->performedOn($car)
                ->causedBy($actor)
                ->event(ActivityEvent::Deactivated->value)
                ->withChanges(['old' => ['is_active' => true], 'attributes' => ['is_active' => false]])
                ->log("Menonaktifkan mobil {$car->brand} {$car->model} ({$car->plate_number})");

            return $car;
        });
    }
}
