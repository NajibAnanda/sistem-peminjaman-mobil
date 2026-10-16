<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use App\Support\BookingCodeGenerator;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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
            // Nomor urut mengikuti bulan pencatatan (created_at) bila diisi state.
            'code' => fn (array $attributes): string => DB::transaction(
                fn (): string => app(BookingCodeGenerator::class)->next($attributes['created_at'] ?? null),
            ),
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

    /**
     * Peminjaman lampau untuk data pengembangan berukuran besar: sebagian besar
     * Selesai (±20% kembali terlambat) dan ±10% Dibatalkan. Waktu mulai dapat
     * ditentukan agar seeder bisa menyusun jadwal per mobil tanpa tumpang tindih.
     */
    public function historical(?CarbonInterface $start = null, ?int $hours = null): static
    {
        return $this->state(function (array $attributes) use ($start, $hours): array {
            $start = CarbonImmutable::instance($start ?? now()->subDays(fake()->numberBetween(2, 365))
                ->setTime(fake()->numberBetween(7, 15), fake()->randomElement([0, 30])));
            $plannedReturn = $start->addHours($hours ?? fake()->numberBetween(2, 30));
            $createdAt = $start->subDays(fake()->numberBetween(0, 5))->subHours(fake()->numberBetween(1, 8));

            if (fake()->boolean(10)) {
                return [
                    'status' => BookingStatus::Cancelled,
                    'start_at' => $start,
                    'planned_return_at' => $plannedReturn,
                    'cancellation_reason' => fake()->randomElement(['Kegiatan ditunda', 'Karyawan berhalangan', 'Memakai kendaraan lain']),
                    'cancelled_at' => $createdAt->addHours(fake()->numberBetween(1, 20)),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            $pickedUp = $start->addMinutes(fake()->numberBetween(-30, 30));
            $returned = fake()->boolean(20)
                ? $plannedReturn->addMinutes(fake()->numberBetween(15, 600))
                : $plannedReturn->subMinutes(fake()->numberBetween(0, 90));

            return [
                'status' => BookingStatus::Completed,
                'start_at' => $start,
                'planned_return_at' => $plannedReturn,
                'picked_up_at' => $pickedUp,
                // Minimal satu jam setelah diambil agar data tetap masuk akal.
                'returned_at' => $returned->max($pickedUp->addHour()),
                'created_at' => $createdAt,
                'updated_at' => $returned,
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
