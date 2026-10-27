<?php

declare(strict_types=1);

namespace App\Actions\Car;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\CarOperationalStatus;
use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateCar
{
    /**
     * Foto referensi boleh menyusul (AGENTS.md 5.15), jadi tidak diminta di sini.
     *
     * @param  array{plate_number: string, brand: string, model: string}  $data
     */
    public function handle(array $data, User $actor): Car
    {
        return DB::transaction(function () use ($data, $actor): Car {
            $car = Car::query()->create([
                ...$data,
                'operational_status' => CarOperationalStatus::Operational,
                'is_active' => true,
            ]);

            activity(ActivityLogName::Car)
                ->performedOn($car)
                ->causedBy($actor)
                ->event(ActivityEvent::Created->value)
                ->withChanges(['attributes' => $car->only(['plate_number', 'brand', 'model'])])
                ->log("Menambah mobil {$car->brand} {$car->model} ({$car->plate_number})");

            return $car;
        });
    }
}
