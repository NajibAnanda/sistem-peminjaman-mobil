<?php

declare(strict_types=1);

namespace App\Actions\Car;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateCar
{
    /**
     * @param  array{plate_number: string, brand: string, model: string}  $data
     */
    public function handle(Car $car, array $data, User $actor): Car
    {
        return DB::transaction(function () use ($car, $data, $actor): Car {
            $car = Car::query()->lockForUpdate()->findOrFail($car->id);
            $before = $car->only(array_keys($data));

            $car->fill($data);
            $changed = array_keys($car->getDirty());
            $car->save();

            if ($changed !== []) {
                activity(ActivityLogName::Car)
                    ->performedOn($car)
                    ->causedBy($actor)
                    ->event(ActivityEvent::Updated->value)
                    ->withChanges([
                        'old' => array_intersect_key($before, array_flip($changed)),
                        'attributes' => $car->only($changed),
                    ])
                    ->log("Mengubah data mobil {$car->brand} {$car->model} ({$car->plate_number})");
            }

            return $car;
        });
    }
}
