<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return list<string>
 */
function staffNames(Assert $page): array
{
    return collect($page->toArray()['props']['staff']['data'])->pluck('name')->sort()->values()->all();
}

beforeEach(function () {
    $this->manager = User::factory()->manager()->create(['name' => 'Zaki Pengelola', 'email' => 'zaki@kantor.test']);
    User::factory()->admin()->create(['name' => 'Rina Admin', 'email' => 'rina@kantor.test']);
    User::factory()->admin()->inactive()->create(['name' => 'Tono Lama', 'email' => 'tono@kantor.test']);
});

test('Pengelola melihat daftar petugas tanpa data sensitif', function () {
    $this->actingAs($this->manager)
        ->get(route('staff.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('staff/index')
            ->has('staff.data', 3)
            ->has('staff.data.0', fn (Assert $row) => $row
                ->hasAll(['id', 'name', 'email', 'role', 'role_label', 'is_active', 'two_factor_enabled', 'must_change_password', 'last_login_at', 'is_self'])
                ->missingAll(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']),
            )
            ->where('query', ['search' => null, 'filters' => ['role' => [], 'status' => []]])
            ->where('roles', [['value' => 'manager', 'label' => 'Pengelola'], ['value' => 'admin', 'label' => 'Admin']])
            ->where('statuses', [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]),
        );
});

test('pencarian dan filter mengembalikan data yang benar', function (array $query, array $expectedNames) {
    $this->actingAs($this->manager)
        ->get(route('staff.index', $query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => expect(staffNames($page))->toBe($expectedNames));
})->with([
    'cari nama' => [['search' => 'rina'], ['Rina Admin']],
    'cari email' => [['search' => 'zaki@'], ['Zaki Pengelola']],
    'satu peran' => [['role' => ['manager']], ['Zaki Pengelola']],
    'dua peran sekaligus' => [['role' => ['manager', 'admin']], ['Rina Admin', 'Tono Lama', 'Zaki Pengelola']],
    'status nonaktif' => [['status' => ['inactive']], ['Tono Lama']],
    'status aktif dan nonaktif' => [['status' => ['active', 'inactive']], ['Rina Admin', 'Tono Lama', 'Zaki Pengelola']],
    'peran dan status' => [['role' => ['admin'], 'status' => ['active']], ['Rina Admin']],
    'pencarian dan filter' => [['search' => 'lama', 'role' => ['admin']], ['Tono Lama']],
    'pencarian dan filter tanpa hasil' => [['search' => 'rina', 'status' => ['inactive']], []],
]);

test('filter yang dipakai dikirim balik agar centang tetap sesuai URL', function () {
    $this->actingAs($this->manager)
        ->get(route('staff.index', ['search' => ' rina ', 'role' => ['admin'], 'status' => ['active', 'inactive']]))
        ->assertInertia(fn (Assert $page) => $page->where('query', [
            'search' => 'rina',
            'filters' => ['role' => ['admin'], 'status' => ['active', 'inactive']],
        ]));
});

test('parameter filter yang tidak valid diabaikan tanpa error', function (array $query, array $expectedFilters) {
    $this->actingAs($this->manager)
        ->get(route('staff.index', $query))
        ->assertOk()
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page) => $page->where('query.filters', $expectedFilters));
})->with([
    'nilai tidak dikenal' => [['role' => ['superadmin']], ['role' => [], 'status' => []]],
    'campuran valid dan tidak' => [['role' => ['superadmin', 'admin']], ['role' => ['admin'], 'status' => []]],
    'bukan array' => [['status' => 'active'], ['role' => [], 'status' => ['active']]],
    'duplikat' => [['status' => ['active', 'active']], ['role' => [], 'status' => ['active']]],
    'kunci tidak dikenal' => [['divisi' => ['Teknik']], ['role' => [], 'status' => []]],
    'nilai bertingkat' => [['role' => [['manager']]], ['role' => [], 'status' => []]],
]);

test('nomor urut halaman 2 dimulai dari 11 dengan 10 data per halaman', function () {
    User::factory()->count(21)->create();

    $this->actingAs($this->manager)
        ->get(route('staff.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('staff.data', 10)
            ->where('staff.meta.per_page', 10)
            ->where('staff.meta.current_page', 2)
            ->where('staff.meta.from', 11)
            ->where('staff.meta.to', 20)
            ->where('staff.meta.total', 24)
            ->where('staff.meta.last_page', 3),
        );
});

test('tautan halaman mempertahankan pencarian dan filter', function () {
    User::factory()->count(12)->admin()->create();

    $this->actingAs($this->manager)
        ->get(route('staff.index', ['role' => ['admin']]))
        ->assertInertia(fn (Assert $page) => $page->where(
            'staff.links.next',
            fn (string $url) => str_contains(urldecode($url), 'role[0]=admin') && str_contains($url, 'page=2'),
        ));
});

test('halaman di luar jangkauan diarahkan ke halaman terakhir', function () {
    $this->actingAs($this->manager)
        ->get(route('staff.index', ['page' => 99]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('staff.meta.current_page', 1)
            ->has('staff.data', 3),
        );
});

test('Admin ditolak membuka Kelola Petugas', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('staff.index'))
        ->assertForbidden();
});
