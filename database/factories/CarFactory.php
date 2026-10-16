<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CarOperationalStatus;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
final class CarFactory extends Factory
{
    /** Merk dan tipe mobil dinas yang umum di Indonesia. */
    private const MODELS = [
        ['Toyota', 'Avanza'],
        ['Toyota', 'Innova Zenix'],
        ['Toyota', 'Fortuner'],
        ['Honda', 'HR-V'],
        ['Honda', 'Mobilio'],
        ['Mitsubishi', 'Xpander'],
        ['Mitsubishi', 'Pajero Sport'],
        ['Daihatsu', 'Terios'],
        ['Suzuki', 'Ertiga'],
        ['Hyundai', 'Stargazer'],
    ];

    /** Kode wilayah plat nomor. */
    private const REGIONS = ['B', 'D', 'F', 'L', 'AB', 'H', 'N'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$brand, $model] = fake()->randomElement(self::MODELS);

        return [
            'plate_number' => sprintf(
                '%s %d %s',
                fake()->randomElement(self::REGIONS),
                fake()->numberBetween(1000, 9999),
                fake()->unique()->regexify('[A-Z]{3}'),
            ),
            'brand' => $brand,
            'model' => $model,
            'operational_status' => CarOperationalStatus::Operational,
            'is_active' => true,
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'operational_status' => CarOperationalStatus::Maintenance,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
