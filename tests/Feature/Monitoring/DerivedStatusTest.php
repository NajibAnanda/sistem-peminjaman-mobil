<?php

use App\Enums\BookingDisplayStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Support\BookingAlerts;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->overdue = Booking::factory()->create([
        'status' => BookingStatus::Active,
        'start_at' => CarbonImmutable::parse('2026-10-20 08:00:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 12:00:00'),
        'picked_up_at' => CarbonImmutable::parse('2026-10-20 08:00:00'),
    ]);
    $this->notPickedUp = Booking::factory()->scheduled()->create([
        'start_at' => CarbonImmutable::parse('2026-10-20 12:00:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 15:00:00'),
    ]);
});

test('tepat di batas waktu belum dihitung Terlambat atau Belum Diambil', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00'));

    expect(BookingAlerts::counts())->toBe(['overdue' => 0, 'not_picked_up' => 0])
        ->and($this->overdue->displayStatus())->toBe(BookingDisplayStatus::Active)
        ->and($this->notPickedUp->displayStatus())->toBe(BookingDisplayStatus::Scheduled);
});

test('satu detik setelah batas waktu dihitung Terlambat dan Belum Diambil', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:01'));

    expect(BookingAlerts::counts())->toBe(['overdue' => 1, 'not_picked_up' => 1])
        ->and($this->overdue->displayStatus())->toBe(BookingDisplayStatus::Overdue)
        ->and($this->notPickedUp->displayStatus())->toBe(BookingDisplayStatus::NotPickedUp);
});

test('peminjaman selesai atau dibatalkan tidak pernah dihitung', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-21 08:00:00'));
    $this->overdue->update(['status' => BookingStatus::Completed, 'returned_at' => now()]);
    $this->notPickedUp->update(['status' => BookingStatus::Cancelled, 'cancelled_at' => now(), 'cancellation_reason' => 'Batal']);

    expect(BookingAlerts::counts())->toBe(['overdue' => 0, 'not_picked_up' => 0]);
});

test('badge menu dibagikan ke semua halaman dan bisa dimuat ulang sendirian', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 13:00:00'));
    Booking::factory()->scheduled()->create([
        'start_at' => CarbonImmutable::parse('2026-10-20 11:00:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 18:00:00'),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('cars.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('alerts', ['overdue' => 1, 'not_picked_up' => 2])
            ->where('pollingIntervalSeconds', 60)
            ->reloadOnly('alerts', fn (Assert $reload) => $reload
                ->where('alerts', ['overdue' => 1, 'not_picked_up' => 2])
                ->missing('cars')),
        );
});

test('interval polling mengikuti config', function () {
    config(['fleet.polling_interval_seconds' => 15]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('pollingIntervalSeconds', 15));
});

test('tamu tidak menerima jumlah peminjaman', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('alerts', null));
});

test('badge diperbarui sesuai waktu server tanpa data baru', function () {
    $user = User::factory()->admin()->create();

    $this->travelTo(CarbonImmutable::parse('2026-10-20 11:59:00'));
    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('alerts.overdue', 0));

    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:05:00'));
    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->reloadOnly('alerts', fn (Assert $reload) => $reload->where('alerts.overdue', 1)));
});
