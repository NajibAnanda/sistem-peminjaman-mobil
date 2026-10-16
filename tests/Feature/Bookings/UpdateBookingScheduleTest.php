<?php

use App\Enums\ActivityEvent;
use App\Enums\BookingDisplayStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 08:00:00'));
    $this->user = User::factory()->admin()->create();
    $this->booking = Booking::factory()->scheduled()->create([
        'start_at' => CarbonImmutable::parse('2026-10-20 10:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 14:00'),
        'purpose' => 'Rapat di kantor cabang',
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function updatePayload(Booking $booking, array $overrides = []): array
{
    return [
        'employee_id' => $booking->employee_id,
        'car_id' => $booking->car_id,
        'start_at' => $booking->start_at->format('Y-m-d\TH:i'),
        'planned_return_at' => $booking->planned_return_at->format('Y-m-d\TH:i'),
        'purpose' => $booking->purpose,
        ...$overrides,
    ];
}

test('form edit memuat data booking dan ketersediaan untuk rentangnya sendiri', function () {
    $this->actingAs($this->user)
        ->get(route('bookings.edit', $this->booking))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookings/form')
            ->where('booking.id', $this->booking->id)
            ->where('cars', fn ($cars) => collect($cars)->firstWhere('id', $this->booking->car_id)['available'] === true),
        );
});

test('edit jadwal mencatat nilai sebelum dan sesudah', function () {
    $employee = Employee::factory()->create();
    $car = Car::factory()->create();

    $this->actingAs($this->user)
        ->put(route('bookings.update', $this->booking), updatePayload($this->booking, [
            'employee_id' => $employee->id,
            'car_id' => $car->id,
            'planned_return_at' => '2026-10-20T16:00',
            'purpose' => 'Survei lokasi proyek',
        ]))
        ->assertRedirect(route('bookings.show', $this->booking))
        ->assertSessionHas('success', 'Jadwal peminjaman berhasil diperbarui.');

    $activity = lastActivity();
    $changes = $activity->attribute_changes->toArray();

    expect($this->booking->refresh()->car_id)->toBe($car->id)
        ->and($activity->event)->toBe(ActivityEvent::Updated->value)
        ->and(array_keys($changes['old']))->toEqualCanonicalizing(['employee_id', 'car_id', 'planned_return_at', 'purpose'])
        ->and($changes['old']['purpose'])->toBe('Rapat di kantor cabang')
        ->and($changes['attributes']['purpose'])->toBe('Survei lokasi proyek')
        ->and($changes['old']['car_id'])->not->toBe($changes['attributes']['car_id'])
        ->and($changes['attributes']['employee_id'])->toBe($employee->id);
});

test('booking Belum Diambil tetap dapat diedit tanpa mengubah waktu mulai', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 11:00:00'));
    expect($this->booking->displayStatus())->toBe(BookingDisplayStatus::NotPickedUp);

    $this->actingAs($this->user)
        ->put(route('bookings.update', $this->booking), updatePayload($this->booking, [
            'purpose' => 'Kunjungan klien',
            'planned_return_at' => '2026-10-20T17:00',
        ]))
        ->assertSessionHasNoErrors();

    expect($this->booking->refresh()->purpose)->toBe('Kunjungan klien')
        ->and($this->booking->start_at->format('H:i'))->toBe('10:00');
});

test('mengubah waktu mulai ke luar toleransi ditolak', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 11:00:00'));

    $this->actingAs($this->user)
        ->put(route('bookings.update', $this->booking), updatePayload($this->booking, [
            'start_at' => '2026-10-20T10:30',
        ]))
        ->assertSessionHasErrors('start_at');

    expect($this->booking->refresh()->start_at->format('H:i'))->toBe('10:00');
});

test('booking tidak bentrok dengan dirinya sendiri saat waktunya digeser', function () {
    $this->actingAs($this->user)
        ->put(route('bookings.update', $this->booking), updatePayload($this->booking, [
            'start_at' => '2026-10-20T12:00',
            'planned_return_at' => '2026-10-20T18:00',
        ]))
        ->assertSessionHasNoErrors();
});

test('edit yang membuat bentrok ditolak', function (string $field) {
    $other = Booking::factory()->scheduled()->create([
        'start_at' => CarbonImmutable::parse('2026-10-20 15:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 18:00'),
    ]);

    $overrides = $field === 'car_id'
        ? ['car_id' => $other->car_id, 'start_at' => '2026-10-20T15:30', 'planned_return_at' => '2026-10-20T16:00']
        : ['car_id' => $other->car_id];

    // Pindah ke mobil lain lalu geser waktu keduanya diuji agar kedua jalur
    // (mobil berubah & waktu berubah) menjalankan cek bentrok.
    if ($field === 'planned_return_at') {
        $this->booking->update(['car_id' => $other->car_id, 'planned_return_at' => CarbonImmutable::parse('2026-10-20 14:30')]);
        $overrides = ['planned_return_at' => '2026-10-20T16:00'];
    }

    $this->actingAs($this->user)
        ->put(route('bookings.update', $this->booking), updatePayload($this->booking->refresh(), $overrides))
        ->assertSessionHasErrors('car_id');

    expect(session('errors')->first('car_id'))->toContain($other->code);
})->with(['car_id', 'planned_return_at']);

test('pindah ke mobil Maintenance ditolak', function () {
    $car = Car::factory()->maintenance()->create();

    $this->actingAs($this->user)
        ->put(route('bookings.update', $this->booking), updatePayload($this->booking, ['car_id' => $car->id]))
        ->assertSessionHasErrors('car_id');
});

test('mobil yang sudah Maintenance tetap boleh dipertahankan saat mengubah data lain', function () {
    // Mobil dipindah ke Maintenance setelah dijadwalkan; tujuan tetap bisa
    // diperbaiki, dan petugas diingatkan saat serah terima.
    $this->booking->car->update(['operational_status' => 'maintenance']);

    $this->actingAs($this->user)
        ->put(route('bookings.update', $this->booking), updatePayload($this->booking, ['purpose' => 'Antar dokumen']))
        ->assertSessionHasNoErrors();
});

test('booking selain Dijadwalkan tidak dapat diedit', function (string $state) {
    $booking = Booking::factory()->{$state}()->create();
    $original = $booking->start_at;

    $this->actingAs($this->user)
        ->put(route('bookings.update', $booking), updatePayload($booking, [
            'start_at' => '2026-10-29T09:17',
            'planned_return_at' => '2026-10-29T12:00',
        ]))
        ->assertInertiaFlash('toast.type', 'error');

    expect($booking->refresh()->start_at->equalTo($original))->toBeTrue();
})->with(['active', 'completed', 'cancelled']);
