<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CarPhoto>
 */
final class CarPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'position' => 1,
            'path' => 'cars/'.Str::uuid().'.jpg',
        ];
    }
}
