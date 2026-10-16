<?php

use App\Enums\ActivityEvent;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00'));
    $this->user = User::factory()->admin()->create();
    $this->active = Booking::factory()->create([
        'status' => BookingStatus::Active,
        'start_at' => CarbonImmutable::parse('2026-10-20 08:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 14:00'),
        'picked_up_at' => CarbonImmutable::parse('2026-10-20 08:00'),
    ]);
});

test('perpanjangan menyimpan riwayat dan memperbarui rencana kembali', function () {
    $this->actingAs($this->user)
        ->post(route('bookings.extend', $this->active), [
            'new_planned_return_at' => '2026-10-20T17:00',
            'reason' => 'Rapat dengan klien diperpanjang',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $extension = BookingExtension::query()->sole();

    expect($this->active->refresh()->planned_return_at->format('H:i'))->toBe('17:00')
        ->and($extension->previous_planned_return_at->format('H:i'))->toBe('14:00')
        ->and($extension->new_planned_return_at->format('H:i'))->toBe('17:00')
        ->and($extension->reason)->toBe('Rapat dengan klien diperpanjang')
        ->and($extension->extended_by)->toBe($this->user->id)
        ->and(lastActivity()->event)->toBe(ActivityEvent::Extended->value);

    $this->get(route('bookings.show', $this->active))
        ->assertInertia(fn (Assert $page) => $page
            ->has('booking.extensions', 1)
            ->where('booking.extensions.0.reason', 'Rapat dengan klien diperpanjang')
            ->where('booking.extensions.0.extended_by_name', $this->user->name),
        );
});

test('perpanjangan yang tidak lebih lambat ditolak', function (string $newReturn) {
    $this->actingAs($this->user)
        ->post(route('bookings.extend', $this->active), [
            'new_planned_return_at' => $newReturn,
            'reason' => 'Salah input',
        ])
        ->assertSessionHasErrors(['new_planned_return_at' => 'Rencana kembali baru harus lebih lambat dari rencana saat ini (20 Okt 2026, 14.00).']);

    expect($this->active->refresh()->planned_return_at->format('H:i'))->toBe('14:00')
        ->and(BookingExtension::query()->count())->toBe(0);
})->with([
    'sama' => ['2026-10-20T14:00'],
    'lebih awal' => ['2026-10-20T13:00'],
]);

test('alasan perpanjangan wajib diisi', function () {
    $this->actingAs($this->user)
        ->post(route('bookings.extend', $this->active), ['new_planned_return_at' => '2026-10-20T17:00'])
        ->assertSessionHasErrors('reason');
});

test('perpanjangan yang bentrok dengan jadwal berikutnya ditolak', function () {
    $next = Booking::factory()->scheduled()->for($this->active->car)->create([
        'start_at' => CarbonImmutable::parse('2026-10-20 16:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 18:00'),
    ]);

    $this->actingAs($this->user)
        ->post(route('bookings.extend', $this->active), [
            'new_planned_return_at' => '2026-10-20T17:00',
            'reason' => 'Rapat diperpanjang',
        ])
        ->assertSessionHasErrors('new_planned_return_at');

    expect(session('errors')->first('new_planned_return_at'))->toContain($next->code)
        ->and($this->active->refresh()->planned_return_at->format('H:i'))->toBe('14:00');

    // Tepat sampai jadwal berikutnya mulai masih boleh.
    $this->post(route('bookings.extend', $this->active), [
        'new_planned_return_at' => '2026-10-20T16:00',
        'reason' => 'Rapat diperpanjang',
    ])->assertSessionHasNoErrors();
});

test('hanya booking Aktif yang dapat diperpanjang', function (string $state) {
    $booking = Booking::factory()->{$state}()->create();

    $this->actingAs($this->user)
        ->post(route('bookings.extend', $booking), [
            'new_planned_return_at' => $booking->planned_return_at->addHours(3)->format('Y-m-d\TH:i'),
            'reason' => 'Diperpanjang',
        ])
        ->assertInertiaFlash('toast.type', 'error');

    expect(BookingExtension::query()->count())->toBe(0);
})->with(['scheduled', 'completed', 'cancelled']);

test('booking Dijadwalkan dapat dibatalkan dengan alasan', function () {
    $booking = Booking::factory()->scheduled()->create();

    $this->actingAs($this->user)
        ->post(route('bookings.cancel', $booking), ['reason' => 'Kegiatan ditunda'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->cancellation_reason)->toBe('Kegiatan ditunda')
        ->and($booking->cancelled_by)->toBe($this->user->id)
        ->and($booking->cancelled_at->format('Y-m-d H:i'))->toBe('2026-10-20 12:00')
        ->and(lastActivity()->event)->toBe(ActivityEvent::Cancelled->value);
});

test('alasan pembatalan wajib diisi', function () {
    $booking = Booking::factory()->scheduled()->create();

    $this->actingAs($this->user)
        ->post(route('bookings.cancel', $booking), ['reason' => '   '])
        ->assertSessionHasErrors('reason');

    expect($booking->refresh()->status)->toBe(BookingStatus::Scheduled);
});

test('booking Aktif, Selesai, atau Dibatalkan tidak dapat dibatalkan', function (string $state) {
    $booking = $state === 'active' ? $this->active : Booking::factory()->{$state}()->create();
    $before = $booking->status;

    $this->actingAs($this->user)
        ->post(route('bookings.cancel', $booking), ['reason' => 'Coba batalkan'])
        ->assertInertiaFlash('toast.type', 'error');

    expect($booking->refresh()->status)->toBe($before);
})->with(['active', 'completed', 'cancelled']);
