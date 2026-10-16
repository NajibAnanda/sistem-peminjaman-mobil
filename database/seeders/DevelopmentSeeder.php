<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

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

    /** Jumlah peminjaman lampau, untuk menguji performa daftar & laporan. */
    private const HISTORICAL_BOOKINGS = 1000;

    private const MIN_DURATION_HOURS = 2;

    private const MAX_DURATION_HOURS = 30;

    private const MIN_GAP_HOURS = 12;

    private const MAX_GAP_HOURS = 96;

    public function run(): void
    {
        $operational = Car::factory()->count(self::OPERATIONAL_CARS)->create();
        $maintenance = Car::factory()->count(self::MAINTENANCE_CARS)->maintenance()->create();
        $inactive = Car::factory()->count(self::INACTIVE_CARS)->inactive()->create();

        $employees = Employee::factory()->count(self::ACTIVE_EMPLOYEES)->create();
        $formerEmployees = Employee::factory()->count(self::INACTIVE_EMPLOYEES)->inactive()->create();

        // Biasanya akun Pengelola pertama dari InitialManagerSeeder.
        $staff = User::query()->orderBy('id')->first() ?? User::factory()->manager()->create();

        $this->seedHistory($operational->concat($maintenance)->concat($inactive), $employees->concat($formerEmployees), $staff);
        $this->seedCurrent($operational, $employees, $staff);
    }

    /**
     * Riwayat setahun ke belakang, disusun mundur per mobil agar peminjaman
     * satu mobil tidak saling tumpang tindih.
     *
     * @param  Collection<int, Car>  $cars
     * @param  Collection<int, Employee>  $employees
     */
    private function seedHistory(Collection $cars, Collection $employees, User $staff): void
    {
        $perCar = intdiv(self::HISTORICAL_BOOKINGS, $cars->count());
        $latestEnd = CarbonImmutable::now()->subDay()->startOfHour();
        $created = [];

        foreach ($cars as $car) {
            $cursor = $latestEnd;

            for ($i = 0; $i < $perCar; $i++) {
                $hours = fake()->numberBetween(self::MIN_DURATION_HOURS, self::MAX_DURATION_HOURS);
                $start = $cursor->subHours($hours);

                $created[] = Booking::factory()->historical($start, $hours)->create([
                    'car_id' => $car->id,
                    'employee_id' => $employees->random()->id,
                    'created_by' => $staff->id,
                    'picked_up_by' => $staff->id,
                    'returned_by' => $staff->id,
                ])->id;

                $cursor = $start->subHours(fake()->numberBetween(self::MIN_GAP_HOURS, self::MAX_GAP_HOURS));
            }
        }

        // Kolom penanggung jawab hanya bermakna sesuai status akhirnya. Dibatasi
        // pada data yang baru dibuat agar data lain di database tidak tersentuh.
        Booking::query()->whereKey($created)->where('status', BookingStatus::Cancelled)
            ->update(['picked_up_by' => null, 'returned_by' => null, 'cancelled_by' => $staff->id]);
    }

    /**
     * Contoh kondisi hari ini agar dashboard, badge, dan Jadwal Armada
     * langsung terisi: Aktif, Terlambat (dengan jadwal berikutnya untuk mobil
     * yang sama), Belum Diambil, dan Dijadwalkan.
     *
     * @param  Collection<int, Car>  $cars
     * @param  Collection<int, Employee>  $employees
     */
    private function seedCurrent(Collection $cars, Collection $employees, User $staff): void
    {
        $now = CarbonImmutable::now()->startOfMinute();
        $people = $employees->shuffle()->values();

        $book = fn (int $car, int $person, BookingStatus $status, CarbonImmutable $start, CarbonImmutable $end, string $purpose) => Booking::factory()->create([
            'car_id' => $cars[$car]->id,
            'employee_id' => $people[$person]->id,
            'status' => $status,
            'start_at' => $start,
            'planned_return_at' => $end,
            'picked_up_at' => $status === BookingStatus::Active ? $start : null,
            'picked_up_by' => $status === BookingStatus::Active ? $staff->id : null,
            'purpose' => $purpose,
            'created_by' => $staff->id,
        ]);

        $book(0, 0, BookingStatus::Active, $now->subHours(2), $now->addHours(4), 'Kunjungan klien di Jakarta Selatan');
        $book(1, 1, BookingStatus::Active, $now->subHours(9), $now->subHours(2), 'Rapat di kantor cabang Bekasi');
        $book(1, 2, BookingStatus::Scheduled, $now->addHours(3), $now->addHours(7), 'Antar dokumen ke instansi');
        $book(2, 3, BookingStatus::Scheduled, $now->subHour(), $now->addHours(5), 'Survei lokasi proyek');
        $book(3, 4, BookingStatus::Scheduled, $now->addHour(), $now->addHours(6), 'Dinas luar kota');
        $book(4, 5, BookingStatus::Scheduled, $now->addDay()->setTime(8, 0), $now->addDay()->setTime(16, 0), 'Kunjungan ke pabrik Cikarang');
        $book(5, 6, BookingStatus::Scheduled, $now->addDays(2)->setTime(9, 0), $now->addDays(3)->setTime(17, 0), 'Dinas luar kota Bandung');
    }
}
