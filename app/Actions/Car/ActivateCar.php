<?php

declare(strict_types=1);

namespace App\Actions\Car;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Exceptions\CarException;
use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ActivateCar
{
    public function handle(Car $car, User $actor): Car
    {
        return DB::transaction(function () use ($car, $actor): Car {
            $car = Car::query()->lockForUpdate()->findOrFail($car->id);

            if ($car->is_active) {
                throw CarException::alreadyActive();
            }

            $car->forceFill(['is_active' => true])->save();

            activity(ActivityLogName::Car)
                ->performedOn($car)
                ->causedBy($actor)
                ->event(ActivityEvent::Activated->value)
                ->withChanges(['old' => ['is_active' => false], 'attributes' => ['is_active' => true]])
                ->log("Mengaktifkan mobil {$car->brand} {$car->model} ({$car->plate_number})");

            return $car;
        });
    }
}
