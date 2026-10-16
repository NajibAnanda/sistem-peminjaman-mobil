<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk pengembangan lokal. Tidak pernah dijalankan di
 * production (lihat DatabaseSeeder).
 */
final class DevelopmentSeeder extends Seeder
{
    private const OPERATIONAL_CARS = 7;

    private const MAINTENANCE_CARS = 2;

    private const INACTIVE_CARS = 1;

    private const ACTIVE_EMPLOYEES = 24;

    private const INACTIVE_EMPLOYEES = 2;

    public function run(): void
    {
        Car::factory()->count(self::OPERATIONAL_CARS)->create();
        Car::factory()->count(self::MAINTENANCE_CARS)->maintenance()->create();
        Car::factory()->count(self::INACTIVE_CARS)->inactive()->create();

        Employee::factory()->count(self::ACTIVE_EMPLOYEES)->create();
        Employee::factory()->count(self::INACTIVE_EMPLOYEES)->inactive()->create();
    }
}
