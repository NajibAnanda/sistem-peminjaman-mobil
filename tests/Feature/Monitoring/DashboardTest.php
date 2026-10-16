<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00'));
    $this->user = User::factory()->admin()->create();
});

function bookingAt(BookingStatus $status, string $start, string $end, array $extra = []): Booking
{
    return Booking::factory()->create([
        'status' => $status,
        'start_at' => CarbonImmutable::parse($start),
        'planned_return_at' => CarbonImmutable::parse($end),
        'picked_up_at' => $status === BookingStatus::Active ? CarbonImmutable::parse($start) : null,
        ...$extra,
    ]);
}

/**
 * @return list<string>
 */
function dashboardCodes(Assert $page, string $prop): array
{
    return collect($page->toArray()['props'][$prop])->pluck('code')->all();
}

test('ringkasan armada menghitung status tampilan mobil', function () {
    $borrowed = Car::factory()->create();
    bookingAt(BookingStatus::Active, '2026-10-20 08:00', '2026-10-20 18:00', ['car_id' => $borrowed->id]);
    Car::factory()->count(2)->create();
    Car::factory()->maintenance()->create();
    Car::factory()->inactive()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('now', now()->toIso8601String())
            ->where('fleet', [
                ['status' => 'available', 'label' => 'Tersedia', 'count' => 2],
                ['status' => 'borrowed', 'label' => 'Dipinjam', 'count' => 1],
                ['status' => 'maintenance', 'label' => 'Maintenance', 'count' => 1],
                ['status' => 'inactive', 'label' => 'Nonaktif', 'count' => 3],
            ]),
        );
});

test('jadwal ambil dan kembali hari ini, Terlambat, dan Belum Diambil berisi peminjaman yang tepat', function () {
    $pickupLater = bookingAt(BookingStatus::Scheduled, '2026-10-20 15:00', '2026-10-21 10:00');
    $pickupMissed = bookingAt(BookingStatus::Scheduled, '2026-10-20 09:00', '2026-10-20 17:00');
    bookingAt(BookingStatus::Scheduled, '2026-10-21 08:00', '2026-10-21 12:00');
    $returnToday = bookingAt(BookingStatus::Active, '2026-10-19 08:00', '2026-10-20 16:00');
    $overdueToday = bookingAt(BookingStatus::Active, '2026-10-20 07:00', '2026-10-20 10:00');
    $overdueOld = bookingAt(BookingStatus::Active, '2026-10-17 07:00', '2026-10-18 10:00');
    $notPickedUpOld = bookingAt(BookingStatus::Scheduled, '2026-10-19 08:00', '2026-10-19 12:00');
    bookingAt(BookingStatus::Active, '2026-10-20 08:00', '2026-10-21 12:00');
    bookingAt(BookingStatus::Completed, '2026-10-20 06:00', '2026-10-20 09:00', ['returned_at' => CarbonImmutable::parse('2026-10-20 09:00')]);
    bookingAt(BookingStatus::Cancelled, '2026-10-20 13:00', '2026-10-20 15:00');

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(function (Assert $page) use ($pickupLater, $pickupMissed, $returnToday, $overdueToday, $overdueOld, $notPickedUpOld) {
            expect(dashboardCodes($page, 'pickupsToday'))->toBe([$pickupMissed->code, $pickupLater->code])
                ->and(dashboardCodes($page, 'returnsToday'))->toBe([$overdueToday->code, $returnToday->code])
                ->and(dashboardCodes($page, 'overdue'))->toBe([$overdueOld->code, $overdueToday->code])
                ->and(dashboardCodes($page, 'notPickedUp'))->toBe([$notPickedUpOld->code, $pickupMissed->code]);

            $page->where('overdue.0.employee.phone', $overdueOld->employee->phone)
                ->where('overdue.0.display_status', 'overdue')
                ->where('alerts', ['overdue' => 2, 'not_picked_up' => 2]);
        });
});

test('daftar Terlambat dibatasi 10 tetapi jumlah di badge tetap lengkap', function () {
    foreach (range(1, 12) as $hour) {
        bookingAt(BookingStatus::Active, '2026-10-19 06:00', sprintf('2026-10-20 %02d:00', $hour - 1));
    }

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('overdue', 10)
            ->where('alerts.overdue', 12));
});

test('jadwal berikutnya untuk mobil yang belum kembali diberi peringatan (FR-23)', function () {
    $overdue = bookingAt(BookingStatus::Active, '2026-10-20 06:00', '2026-10-20 10:00');
    $next = bookingAt(BookingStatus::Scheduled, '2026-10-20 15:00', '2026-10-20 18:00', ['car_id' => $overdue->car_id]);
    // Mobil lain yang sedang dipinjam tetapi belum terlambat: tidak ada peringatan.
    $onTime = bookingAt(BookingStatus::Active, '2026-10-20 08:00', '2026-10-20 14:00');
    $afterOnTime = bookingAt(BookingStatus::Scheduled, '2026-10-20 14:00', '2026-10-20 18:00', ['car_id' => $onTime->car_id]);

    $this->actingAs($this->user);

    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('pickupsToday', fn ($rows) => collect($rows)->pluck('awaiting_return_code', 'code')->all() === [
            $afterOnTime->code => null,
            $next->code => $overdue->code,
        ])
        ->where('overdue.0.awaiting_return_code', null));

    $this->get(route('bookings.index'))->assertInertia(fn (Assert $page) => $page
        ->where('bookings.data', fn ($rows) => collect($rows)->firstWhere('code', $next->code)['awaiting_return_code'] === $overdue->code));

    $this->get(route('bookings.show', $next))->assertInertia(fn (Assert $page) => $page
        ->where('booking.awaiting_return_code', $overdue->code));

    // Setelah mobil dikembalikan, peringatan hilang.
    $overdue->update(['status' => BookingStatus::Completed, 'returned_at' => now()]);

    $this->get(route('bookings.show', $next))->assertInertia(fn (Assert $page) => $page
        ->where('booking.awaiting_return_code', null));
});

test('jumlah query dashboard, peminjaman, dan jadwal armada tidak bertambah seiring jumlah data', function (string $routeName) {
    $this->actingAs($this->user);
    $countQueries = function () use ($routeName): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route($routeName))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $seed = function (int $times): void {
        foreach (range(1, $times) as $i) {
            $car = Car::factory()->create();
            bookingAt(BookingStatus::Active, '2026-10-20 06:00', '2026-10-20 10:00', ['car_id' => $car->id]);
            bookingAt(BookingStatus::Scheduled, '2026-10-20 11:00', '2026-10-20 18:00', ['car_id' => $car->id]);
        }
    };

    $seed(2);
    $few = $countQueries();
    $seed(8);

    expect($countQueries())->toBe($few);
})->with(['dashboard', 'bookings.index', 'schedule.index']);

test('tamu dan akun nonaktif tidak dapat membuka dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->inactive()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('Admin dan Pengelola dapat membuka dashboard', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('dashboard'))
        ->assertOk();
})->with(['admin', 'manager']);
