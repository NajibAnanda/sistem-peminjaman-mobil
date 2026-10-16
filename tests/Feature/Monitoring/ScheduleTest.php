<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Rabu; minggu ini = Senin 19 Okt s.d. Minggu 25 Okt 2026.
    $this->travelTo(CarbonImmutable::parse('2026-10-21 12:00:00'));
    $this->user = User::factory()->admin()->create();
    $this->car = Car::factory()->create(['plate_number' => 'B 1000 AAA']);
});

function timelineBooking(Car $car, BookingStatus $status, string $start, string $end, array $extra = []): Booking
{
    return Booking::factory()->for($car)->create([
        'status' => $status,
        'start_at' => CarbonImmutable::parse($start),
        'planned_return_at' => CarbonImmutable::parse($end),
        ...$extra,
    ]);
}

/**
 * @return array<string, array{0: string, 1: string}>
 */
function timelineRanges(Assert $page): array
{
    return collect($page->toArray()['props']['bookings'])
        ->mapWithKeys(fn (array $booking) => [$booking['code'] => [
            CarbonImmutable::parse($booking['start_at'])->format('Y-m-d H:i'),
            CarbonImmutable::parse($booking['end_at'])->format('Y-m-d H:i'),
        ]])
        ->all();
}

test('minggu ini dimulai Senin dan menampilkan 7 hari', function () {
    $this->actingAs($this->user)
        ->get(route('schedule.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('schedule/index')
            ->where('week.start', CarbonImmutable::parse('2026-10-19 00:00:00')->toIso8601String())
            ->where('week.end', CarbonImmutable::parse('2026-10-26 00:00:00')->toIso8601String())
            ->where('week.previous', '2026-10-12')
            ->where('week.next', '2026-10-26')
            ->where('week.is_current', true)
            ->where('week.days', ['2026-10-19', '2026-10-20', '2026-10-21', '2026-10-22', '2026-10-23', '2026-10-24', '2026-10-25']),
        );
});

test('parameter minggu menampilkan minggu yang berisi tanggal itu', function () {
    $this->actingAs($this->user)
        ->get(route('schedule.index', ['week' => '2026-11-05']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('week.days.0', '2026-11-02')
            ->where('week.is_current', false));
});

test('parameter minggu yang tidak valid diabaikan', function (string $week) {
    $this->actingAs($this->user)
        ->get(route('schedule.index', ['week' => $week]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('week.days.0', '2026-10-19'));
})->with(['kemarin', '2026-02-31', '2026-13-01', '20261105']);

test('peminjaman tampil pada rentang yang benar, termasuk yang melewati batas minggu', function () {
    $scheduled = timelineBooking($this->car, BookingStatus::Scheduled, '2026-10-23 08:00', '2026-10-23 17:00');
    $fromLastWeek = timelineBooking($this->car, BookingStatus::Completed, '2026-10-17 08:00', '2026-10-19 10:00', [
        'picked_up_at' => CarbonImmutable::parse('2026-10-17 07:30'),
        'returned_at' => CarbonImmutable::parse('2026-10-19 11:15'),
    ]);
    $overdue = timelineBooking($this->car, BookingStatus::Active, '2026-10-20 08:00', '2026-10-20 18:00', [
        'picked_up_at' => CarbonImmutable::parse('2026-10-20 08:10'),
    ]);
    $intoNextWeek = timelineBooking($this->car, BookingStatus::Scheduled, '2026-10-25 20:00', '2026-10-26 10:00');
    timelineBooking($this->car, BookingStatus::Cancelled, '2026-10-22 08:00', '2026-10-22 12:00');
    timelineBooking($this->car, BookingStatus::Scheduled, '2026-10-26 08:00', '2026-10-26 12:00');
    timelineBooking($this->car, BookingStatus::Completed, '2026-10-12 08:00', '2026-10-12 12:00', ['returned_at' => CarbonImmutable::parse('2026-10-12 12:00')]);

    $this->actingAs($this->user)
        ->get(route('schedule.index'))
        ->assertInertia(fn (Assert $page) => expect(timelineRanges($page))->toBe([
            // Waktu aktual dipakai bila sudah ada.
            $fromLastWeek->code => ['2026-10-17 07:30', '2026-10-19 11:15'],
            // Terlambat dianggap memakai mobil sampai sekarang.
            $overdue->code => ['2026-10-20 08:10', '2026-10-21 12:00'],
            $scheduled->code => ['2026-10-23 08:00', '2026-10-23 17:00'],
            $intoNextWeek->code => ['2026-10-25 20:00', '2026-10-26 10:00'],
        ]));
});

test('blok membawa status tampilan dan data seperlunya', function () {
    $booking = timelineBooking($this->car, BookingStatus::Scheduled, '2026-10-21 09:00', '2026-10-21 17:00');

    $this->actingAs($this->user)
        ->get(route('schedule.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('bookings.0', [
                'id' => $booking->id,
                'code' => $booking->code,
                'car_id' => $this->car->id,
                'employee_name' => $booking->employee->name,
                'purpose' => $booking->purpose,
                'display_status' => 'not_picked_up',
                'display_status_label' => 'Belum Diambil',
                'start_at' => $booking->start_at->toIso8601String(),
                'end_at' => $booking->planned_return_at->toIso8601String(),
            ]));
});

test('mobil nonaktif hanya tampil bila punya peminjaman di minggu itu', function () {
    $retired = Car::factory()->inactive()->create(['plate_number' => 'B 2000 BBB']);
    Car::factory()->inactive()->create(['plate_number' => 'B 3000 CCC']);
    timelineBooking($retired, BookingStatus::Completed, '2026-10-19 08:00', '2026-10-19 12:00', [
        'picked_up_at' => CarbonImmutable::parse('2026-10-19 08:00'),
        'returned_at' => CarbonImmutable::parse('2026-10-19 12:00'),
    ]);

    $this->actingAs($this->user)
        ->get(route('schedule.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cars', fn ($cars) => collect($cars)->pluck('plate_number')->all() === ['B 1000 AAA', 'B 2000 BBB'])
            ->where('cars.1.display_status', 'inactive'));

    $this->get(route('schedule.index', ['week' => '2026-10-26']))
        ->assertInertia(fn (Assert $page) => $page->has('cars', 1));
});

test('tamu dan akun nonaktif tidak dapat membuka Jadwal Armada', function () {
    $this->get(route('schedule.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->inactive()->create())
        ->get(route('schedule.index'))
        ->assertRedirect(route('login'));
});
