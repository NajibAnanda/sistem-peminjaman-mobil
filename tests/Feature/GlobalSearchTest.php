<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use App\Support\Search\GlobalSearch;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->car = Car::factory()->create(['plate_number' => 'B 1234 SRI', 'brand' => 'Toyota', 'model' => 'Avanza']);
    $this->employee = Employee::factory()->create(['name' => 'Sri Wahyuni', 'employee_number' => 'KRY900', 'division' => 'Keuangan']);
    $this->booking = Booking::factory()->scheduled()->for($this->car)->for($this->employee)->create();
    $this->staff = User::factory()->admin()->create(['name' => 'Sri Petugas', 'email' => 'sri@kantor.test']);
});

/**
 * @return array<string, list<array<string, mixed>>>
 */
function searchGroups(TestResponse $response): array
{
    return collect($response->json('groups'))->mapWithKeys(fn (array $group) => [$group['key'] => $group['items']])->all();
}

test('Pengelola mendapat semua kategori yang cocok', function () {
    $response = $this->actingAs(User::factory()->manager()->create())
        ->getJson(route('search', ['q' => 'sri']))
        ->assertOk()
        ->assertJsonPath('groups.0.label', 'Peminjaman');

    expect(array_keys(searchGroups($response)))->toBe(['bookings', 'cars', 'employees', 'staff']);
});

test('Admin tidak mendapat hasil Petugas walau ada yang cocok', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('search', ['q' => 'sri']))
        ->assertOk();

    expect(array_keys(searchGroups($response)))->toBe(['bookings', 'cars', 'employees']);
});

test('hasil hanya berisi field untuk tampilan dan tertaut ke halaman terkait', function () {
    $groups = searchGroups($this->actingAs(User::factory()->manager()->create())
        ->getJson(route('search', ['q' => 'sri'])));

    expect($groups['cars'][0])->toBe([
        'id' => $this->car->id,
        'title' => 'B 1234 SRI',
        'subtitle' => 'Toyota Avanza',
        'url' => route('cars.show', $this->car),
    ])
        ->and($groups['employees'][0]['url'])->toBe(route('employees.index', ['search' => 'KRY900']))
        ->and($groups['bookings'][0]['title'])->toBe($this->booking->code)
        ->and($groups['bookings'][0]['url'])->toBe(route('bookings.show', $this->booking))
        ->and($groups['staff'][0])->toBe([
            'id' => $this->staff->id,
            'title' => 'Sri Petugas',
            'subtitle' => 'sri@kantor.test · Admin',
            'url' => route('staff.index', ['search' => 'sri@kantor.test']),
        ])
        ->and(json_encode($groups))->not->toContain('password')
        ->not->toContain('two_factor');
});

test('peminjaman bisa dicari lewat kode, nama karyawan, dan plat nomor', function (Closure $term) {
    $groups = searchGroups($this->actingAs(User::factory()->admin()->create())
        ->getJson(route('search', ['q' => $term->call($this)])));

    expect(collect($groups['bookings'] ?? [])->pluck('id')->all())->toContain($this->booking->id);
})->with([
    'kode' => [fn () => $this->booking->code],
    'nama karyawan' => [fn () => 'wahyuni'],
    'plat nomor' => [fn () => '1234 SRI'],
]);

test('jumlah hasil dibatasi per kategori', function () {
    Car::factory()->count(GlobalSearch::LIMIT_PER_CATEGORY + 3)->create(['brand' => 'Daihatsu']);

    $groups = searchGroups($this->actingAs(User::factory()->admin()->create())
        ->getJson(route('search', ['q' => 'daihatsu'])));

    expect($groups['cars'])->toHaveCount(GlobalSearch::LIMIT_PER_CATEGORY);
});

test('kata kunci kosong, terlalu pendek, atau terlalu panjang ditolak', function (?string $q) {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('search', array_filter(['q' => $q])))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
})->with([null, 'a', str_repeat('a', 101)]);

test('tidak ada hasil mengembalikan daftar grup kosong', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('search', ['q' => 'tidak-ada-yang-cocok']))
        ->assertOk()
        ->assertExactJson(['groups' => []]);
});

test('pencarian wajib login dan akun aktif', function () {
    $this->getJson(route('search', ['q' => 'sri']))->assertUnauthorized();
    $this->get(route('search', ['q' => 'sri']))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->inactive()->create())
        ->get(route('search', ['q' => 'sri']))
        ->assertRedirect(route('login'));
    $this->assertGuest();
});
