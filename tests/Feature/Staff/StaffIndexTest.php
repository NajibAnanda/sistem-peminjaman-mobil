<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('Pengelola melihat daftar petugas tanpa data sensitif', function () {
    $manager = User::factory()->manager()->create(['name' => 'Ani Pengelola']);
    User::factory()->withTwoFactor()->create(['name' => 'Budi Admin']);

    $this->actingAs($manager)
        ->get(route('staff.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('staff/index')
            ->has('staff.data', 2)
            ->has('staff.data.0', fn (Assert $row) => $row
                ->where('name', 'Ani Pengelola')
                ->where('role', 'manager')
                ->where('role_label', 'Pengelola')
                ->where('is_self', true)
                ->where('two_factor_enabled', false)
                ->hasAll(['id', 'email', 'is_active', 'must_change_password', 'last_login_at'])
                ->missingAll(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']),
            )
            ->where('staff.data.1.two_factor_enabled', true)
            ->has('roles', 2)
            ->has('staff.meta.total')
            ->has('staff.links.next'),
        );
});

test('daftar petugas dapat dicari dan difilter', function (array $query, array $expectedNames) {
    $manager = User::factory()->manager()->create(['name' => 'Zaki Pengelola', 'email' => 'zaki@kantor.test']);
    User::factory()->create(['name' => 'Rina Admin', 'email' => 'rina@kantor.test']);
    User::factory()->inactive()->create(['name' => 'Tono Lama', 'email' => 'tono@kantor.test']);

    $this->actingAs($manager)
        ->get(route('staff.index', $query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('staff.data', fn ($rows) => collect($rows)->pluck('name')->sort()->values()->all() === $expectedNames),
        );
})->with([
    'cari nama' => [['search' => 'rina'], ['Rina Admin']],
    'cari email' => [['search' => 'zaki@'], ['Zaki Pengelola']],
    'peran Pengelola' => [['role' => 'manager'], ['Zaki Pengelola']],
    'status nonaktif' => [['status' => 'inactive'], ['Tono Lama']],
    'status aktif' => [['status' => 'active'], ['Rina Admin', 'Zaki Pengelola']],
]);

test('nilai filter yang tidak valid ditolak', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->get(route('staff.index', ['role' => 'superadmin']))
        ->assertSessionHasErrors('role');
});

test('daftar petugas dipaginasi di server', function () {
    $manager = User::factory()->manager()->create();
    User::factory()->count(config('fleet.per_page') + 5)->create();

    $this->actingAs($manager)
        ->get(route('staff.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('staff.data', config('fleet.per_page'))
            ->where('staff.meta.total', config('fleet.per_page') + 6)
            ->where('staff.meta.last_page', 2),
        );
});

test('Admin ditolak membuka Kelola Petugas', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('staff.index'))
        ->assertForbidden();
});
