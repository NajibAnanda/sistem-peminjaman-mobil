<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use App\Support\BookingCodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * Kode dibuat lewat BookingCodeGenerator agar tidak pernah bentrok dengan
 * booking yang dijadwalkan lewat aplikasi di test yang sama.
 *
 * @extends Factory<Booking>
 */
final class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 14))->setTime(fake()->numberBetween(7, 15), 0);

        return [
            'code' => fn (): string => DB::transaction(fn (): string => app(BookingCodeGenerator::class)->next()),
            'employee_id' => Employee::factory(),
            'car_id' => Car::factory(),
            'purpose' => fake()->randomElement([
                'Kunjungan klien',
                'Dinas luar kota',
                'Rapat di kantor cabang',
                'Survei lokasi proyek',
                'Antar dokumen ke instansi',
            ]),
            'start_at' => $start,
            'planned_return_at' => $start->addHours(fake()->numberBetween(3, 30)),
            'status' => BookingStatus::Scheduled,
            'created_by' => User::factory(),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Scheduled,
        ]);
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $start = now()->subHours(2);

            return [
                'status' => BookingStatus::Active,
                'start_at' => $start,
                'planned_return_at' => $start->addHours(8),
                'picked_up_at' => $start,
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $start = now()->subDays(5);

            return [
                'status' => BookingStatus::Completed,
                'start_at' => $start,
                'planned_return_at' => $start->addHours(8),
                'picked_up_at' => $start,
                'returned_at' => $start->addHours(7),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancellation_reason' => 'Kegiatan dibatalkan',
            'cancelled_at' => now(),
        ]);
    }
}
