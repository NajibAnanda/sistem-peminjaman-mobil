<?php

use App\Enums\ActivityEvent;
use App\Enums\CarOperationalStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Inertia\Support\SessionKey;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
});

test('mobil dapat diubah ke Maintenance dan kembali Operasional', function () {
    $car = Car::factory()->create();
    Booking::factory()->for($car)->create(); // jadwal mendatang tidak menghalangi (FR-07)

    $this->actingAs($this->user)
        ->put(route('cars.operational-status', $car), ['operational_status' => 'maintenance'])
        ->assertSessionHas('success');

    expect($car->fresh()->operational_status)->toBe(CarOperationalStatus::Maintenance)
        ->and(lastActivity()->event)->toBe(ActivityEvent::OperationalStatusChanged->value)
        ->and(lastActivity()->attribute_changes->toArray())->toBe([
            'old' => ['operational_status' => 'operational'],
            'attributes' => ['operational_status' => 'maintenance'],
        ]);

    $this->put(route('cars.operational-status', $car), ['operational_status' => 'operational']);

    expect($car->fresh()->operational_status)->toBe(CarOperationalStatus::Operational);
});

test('mobil yang sedang dipinjam tidak dapat diubah ke Maintenance', function () {
    $car = Car::factory()->create();
    $booking = Booking::factory()->active()->for($car)->create();

    $this->actingAs($this->user)
        ->put(route('cars.operational-status', $car), ['operational_status' => 'maintenance'])
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', "Mobil sedang dipinjam ({$booking->code}) sehingga tidak dapat diubah ke Maintenance. Tunggu sampai mobil dikembalikan.");

    expect($car->fresh()->operational_status)->toBe(CarOperationalStatus::Operational);
});

test('status mobil nonaktif tidak dapat diubah dan nilai status tidak dikenal ditolak', function () {
    $inactive = Car::factory()->inactive()->create();

    $this->actingAs($this->user)
        ->put(route('cars.operational-status', $inactive), ['operational_status' => 'maintenance'])
        ->assertInertiaFlash('toast.type', 'error');

    $this->put(route('cars.operational-status', Car::factory()->create()), ['operational_status' => 'rusak'])
        ->assertSessionHasErrors('operational_status');
});

test('mobil dapat dinonaktifkan dan diaktifkan kembali', function () {
    $car = Car::factory()->create();
    Booking::factory()->completed()->for($car)->create();
    Booking::factory()->cancelled()->for($car)->create();

    $this->actingAs($this->user)->post(route('cars.deactivate', $car))->assertSessionHas('success');
    expect($car->fresh()->is_active)->toBeFalse()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Deactivated->value);

    $this->post(route('cars.activate', $car))->assertSessionHas('success');
    expect($car->fresh()->is_active)->toBeTrue();
});

test('mobil dengan peminjaman berjalan tidak dapat dinonaktifkan (FR-10)', function (string $state) {
    $car = Car::factory()->create();
    $booking = Booking::factory()->{$state}()->for($car)->create();

    $response = $this->actingAs($this->user)
        ->post(route('cars.deactivate', $car))
        ->assertInertiaFlash('toast.type', 'error');

    $message = $response->baseResponse->getSession()->get(SessionKey::FLASH_DATA)['toast']['message'];

    expect($car->fresh()->is_active)->toBeTrue()
        ->and($message)->toContain($booking->code);
})->with(['dijadwalkan' => 'scheduled', 'aktif' => 'active']);

test('menonaktifkan mobil yang sudah nonaktif ditolak', function () {
    $this->actingAs($this->user)
        ->post(route('cars.deactivate', Car::factory()->inactive()->create()))
        ->assertInertiaFlash('toast.message', 'Mobil ini sudah nonaktif.');
});

test('tamu tidak dapat mengubah status mobil', function () {
    $car = Car::factory()->create();

    $this->put(route('cars.operational-status', $car), ['operational_status' => 'maintenance'])->assertRedirect(route('login'));
    $this->post(route('cars.deactivate', $car))->assertRedirect(route('login'));

    expect($car->fresh()->is_active)->toBeTrue();
});
