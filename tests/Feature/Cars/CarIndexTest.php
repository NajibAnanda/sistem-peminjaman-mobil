<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return list<string>
 */
function carPlates(Assert $page): array
{
    return collect($page->toArray()['props']['cars']['data'])->pluck('plate_number')->sort()->values()->all();
}

beforeEach(function () {
    $this->user = User::factory()->admin()->create();

    Car::factory()->create(['plate_number' => 'B 1111 AAA', 'brand' => 'Toyota', 'model' => 'Avanza']);
    $borrowed = Car::factory()->create(['plate_number' => 'B 2222 BBB', 'brand' => 'Honda', 'model' => 'HR-V']);
    Booking::factory()->active()->for($borrowed)->create();
    Car::factory()->maintenance()->create(['plate_number' => 'D 3333 CCC', 'brand' => 'Toyota', 'model' => 'Innova']);
    Car::factory()->inactive()->create(['plate_number' => 'L 4444 DDD', 'brand' => 'Suzuki', 'model' => 'Ertiga']);
});

test('daftar mobil menampilkan status turunan dan penanda foto', function () {
    $complete = Car::query()->where('plate_number', 'B 1111 AAA')->sole();
    CarPhoto::factory()->for($complete)->create(['position' => 1]);
    CarPhoto::factory()->for($complete)->create(['position' => 2]);

    $this->actingAs($this->user)
        ->get(route('cars.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cars/index')
            ->has('cars.data', 4)
            ->where('cars.data', fn ($rows) => collect($rows)->mapWithKeys(fn ($car) => [
                $car['plate_number'] => [$car['display_status'], $car['photos_complete']],
            ])->all() === [
                'B 1111 AAA' => ['available', true],
                'B 2222 BBB' => ['borrowed', false],
                'D 3333 CCC' => ['maintenance', false],
                'L 4444 DDD' => ['inactive', false],
            ])
            ->has('cars.data.0.photos', 2)
            ->where('statuses.0', ['value' => 'available', 'label' => 'Tersedia']),
        );
});

test('pencarian dan filter status turunan mengembalikan data yang benar', function (array $query, array $expected) {
    $this->actingAs($this->user)
        ->get(route('cars.index', $query))
        ->assertInertia(fn (Assert $page) => expect(carPlates($page))->toBe($expected));
})->with([
    'cari plat' => [['search' => '2222'], ['B 2222 BBB']],
    'cari merk' => [['search' => 'toyota'], ['B 1111 AAA', 'D 3333 CCC']],
    'tersedia' => [['status' => ['available']], ['B 1111 AAA']],
    'dipinjam' => [['status' => ['borrowed']], ['B 2222 BBB']],
    'maintenance' => [['status' => ['maintenance']], ['D 3333 CCC']],
    'nonaktif' => [['status' => ['inactive']], ['L 4444 DDD']],
    'tersedia atau dipinjam' => [['status' => ['available', 'borrowed']], ['B 1111 AAA', 'B 2222 BBB']],
    'cari dan filter' => [['search' => 'toyota', 'status' => ['maintenance']], ['D 3333 CCC']],
]);

test('parameter filter yang tidak valid diabaikan', function () {
    $this->actingAs($this->user)
        ->get(route('cars.index', ['status' => ['rusak', 'available'], 'lain' => 'x']))
        ->assertOk()
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page) => $page->where('query.filters', ['status' => ['available']]));
});

test('nomor urut halaman 2 dimulai dari 11', function () {
    Car::factory()->count(10)->create();

    $this->actingAs($this->user)
        ->get(route('cars.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cars.meta.from', 11)
            ->where('cars.meta.total', 14)
            ->has('cars.data', 4),
        );
});

test('tamu diarahkan ke login dan Pengelola juga dapat membuka Data Mobil', function () {
    $this->get(route('cars.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->manager()->create())
        ->get(route('cars.index'))
        ->assertOk();
});
