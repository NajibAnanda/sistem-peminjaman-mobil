<?php

use App\Models\Employee;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * @return list<string>
 */
function employeeNames(Assert $page): array
{
    return collect($page->toArray()['props']['employees']['data'])->pluck('name')->sort()->values()->all();
}

beforeEach(function () {
    /** @var TestCase $this */
    $this->user = User::factory()->admin()->create();

    Employee::factory()->create(['name' => 'Ani Lestari', 'division' => 'Keuangan', 'phone' => '081111111111']);
    Employee::factory()->create(['name' => 'Budi Santoso', 'division' => 'Teknik', 'phone' => '082222222222']);
    Employee::factory()->inactive()->create(['name' => 'Citra Dewi', 'division' => 'SDM', 'phone' => '083333333333']);
});

test('daftar karyawan dikirim dengan opsi divisi dari config', function () {
    $this->actingAs($this->user)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/index')
            ->has('employees.data', 3)
            ->has('employees.data.0', fn (Assert $row) => $row->hasAll(['id', 'name', 'division', 'phone', 'is_active']))
            ->where('divisions', [
                ['value' => 'Keuangan', 'label' => 'Keuangan', 'can_delete' => false],
                ['value' => 'SDM', 'label' => 'SDM', 'can_delete' => false],
                ['value' => 'Teknik', 'label' => 'Teknik', 'can_delete' => false],
            ]),
        );
});

test('pencarian dan filter mengembalikan data yang benar', function (array $query, array $expected) {
    $this->actingAs($this->user)
        ->get(route('employees.index', $query))
        ->assertInertia(fn (Assert $page) => expect(employeeNames($page))->toBe($expected));
})->with([
    'cari nama' => [['search' => 'lestari'], ['Ani Lestari']],
    'cari no HP' => [['search' => '0833'], ['Citra Dewi']],
    'divisi' => [['division' => ['Teknik', 'SDM']], ['Budi Santoso', 'Citra Dewi']],
    'status aktif' => [['status' => ['active']], ['Ani Lestari', 'Budi Santoso']],
    'divisi dan status' => [['division' => ['SDM'], 'status' => ['active']], []],
]);

test('divisi atau status yang tidak dikenal diabaikan', function () {
    $this->actingAs($this->user)
        ->get(route('employees.index', ['division' => ['Pemasaran'], 'status' => 'hapus']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('query.filters', ['division' => [], 'status' => []])
            ->has('employees.data', 3),
        );
});

test('nomor urut halaman 2 dimulai dari 11', function () {
    Employee::factory()->count(9)->create();

    $this->actingAs($this->user)
        ->get(route('employees.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('employees.meta.from', 11)
            ->has('employees.data', 2),
        );
});

test('tamu diarahkan ke login', function () {
    $this->get(route('employees.index'))->assertRedirect(route('login'));
});
