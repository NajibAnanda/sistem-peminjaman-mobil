<?php

use App\Enums\ActivityEvent;
use App\Enums\CarOperationalStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
});

test('mobil baru dibuat dengan plat nomor baku lalu diarahkan ke detail', function () {
    $response = $this->actingAs($this->user)->post(route('cars.store'), [
        'plate_number' => ' b1234abc ',
        'brand' => ' Toyota ',
        'model' => 'Avanza',
    ]);

    $car = Car::query()->sole();

    $response->assertRedirect(route('cars.show', $car));

    expect($car->plate_number)->toBe('B 1234 ABC')
        ->and($car->brand)->toBe('Toyota')
        ->and($car->operational_status)->toBe(CarOperationalStatus::Operational)
        ->and($car->is_active)->toBeTrue()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Created->value)
        ->and(lastActivity()->causer_id)->toBe($this->user->id);
});

test('validasi data mobil', function (array $data, string $field, string $message) {
    Car::factory()->create(['plate_number' => 'B 1234 ABC']);

    $this->actingAs($this->user)
        ->post(route('cars.store'), [...['plate_number' => 'D 1 X', 'brand' => 'Toyota', 'model' => 'Avanza'], ...$data])
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'plat duplikat beda penulisan' => [['plate_number' => 'b-1234-abc'], 'plate_number', 'Plat nomor sudah dipakai.'],
    'format plat salah' => [['plate_number' => '1234 B'], 'plate_number', 'Format plat nomor tidak valid. Tulis kode wilayah, angka, lalu huruf akhiran. Contoh: B 1234 ABC atau AB 12 CD.'],
    'merk kosong' => [['brand' => ''], 'brand', 'Merk wajib diisi.'],
    'tipe terlalu panjang' => [['model' => str_repeat('a', 51)], 'model', 'Tipe tidak boleh lebih dari 50 karakter.'],
]);

test('data mobil dapat diubah dan nilai sebelum/sesudah tercatat', function () {
    $car = Car::factory()->create(['plate_number' => 'B 1234 ABC', 'model' => 'Avanza']);

    $this->actingAs($this->user)
        ->put(route('cars.update', $car), ['plate_number' => 'B 1234 ABC', 'brand' => $car->brand, 'model' => 'Avanza Veloz'])
        ->assertSessionHasNoErrors();

    expect($car->fresh()->model)->toBe('Avanza Veloz')
        ->and(lastActivity()->attribute_changes->toArray())->toBe([
            'old' => ['model' => 'Avanza'],
            'attributes' => ['model' => 'Avanza Veloz'],
        ]);
});

test('detail mobil menampilkan peminjaman aktif dan jadwal mendatang', function () {
    $car = Car::factory()->create();
    $active = Booking::factory()->active()->for($car)->create();
    $scheduled = Booking::factory()->for($car)->create();

    $this->actingAs($this->user)
        ->get(route('cars.show', $car))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cars/show')
            ->where('car.id', $car->id)
            ->where('car.display_status', 'borrowed')
            ->where('activeBooking.code', $active->code)
            ->has('scheduledBookings', 1)
            ->where('scheduledBookings.0.code', $scheduled->code)
            ->where('maxUploadKb', 5120),
        );
});

test('tamu tidak dapat membuat atau melihat mobil', function () {
    $car = Car::factory()->create();

    $this->post(route('cars.store'), ['plate_number' => 'B 1 A', 'brand' => 'X', 'model' => 'Y'])->assertRedirect(route('login'));
    $this->get(route('cars.show', $car))->assertRedirect(route('login'));

    expect(Car::query()->count())->toBe(1);
});
