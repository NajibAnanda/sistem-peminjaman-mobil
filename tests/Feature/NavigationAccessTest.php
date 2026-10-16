<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const COMMON_ROUTES = [
    'dashboard',
    'schedule.index',
    'bookings.index',
    'history.index',
    'cars.index',
    'employees.index',
    'profile.edit',
    'appearance.edit',
];

const MANAGER_ROUTES = [
    'staff.index',
    'activity-log.index',
];

dataset('halaman umum', COMMON_ROUTES);
dataset('halaman pengelola', MANAGER_ROUTES);
dataset('semua halaman', [...COMMON_ROUTES, ...MANAGER_ROUTES]);

test('tamu diarahkan ke login', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with('semua halaman');

test('Admin dan Pengelola dapat membuka halaman umum', function (string $routeName, string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route($routeName))->assertOk();
})->with('halaman umum')->with(['manager', 'admin']);

test('Pengelola dapat membuka halaman khusus Pengelola', function (string $routeName) {
    $this->actingAs(User::factory()->manager()->create())
        ->get(route($routeName))
        ->assertOk();
})->with('halaman pengelola');

test('Admin ditolak membuka halaman khusus Pengelola walau mengetik URL langsung', function (string $routeName) {
    $this->actingAs(User::factory()->create())
        ->get(route($routeName))
        ->assertForbidden();
})->with('halaman pengelola');

test('akun nonaktif dikeluarkan saat membuka halaman apa pun', function (string $routeName) {
    $this->actingAs(User::factory()->manager()->inactive()->create())
        ->get(route($routeName))
        ->assertRedirect(route('login'));

    $this->assertGuest();
})->with('semua halaman');

test('halaman utama diarahkan ke dashboard', function () {
    $this->get(route('home'))->assertRedirect('/dashboard');
});

test('props akun hanya berisi field yang dibutuhkan tanpa data sensitif', function () {
    $user = User::factory()->withTwoFactor()->create(['name' => 'Siti Admin']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', [
                'id' => $user->id,
                'name' => 'Siti Admin',
                'email' => $user->email,
                'role' => 'admin',
                'role_label' => 'Admin',
                'two_factor_enabled' => true,
            ])
            ->where('timezone', 'Asia/Jakarta'),
        );
});

test('hak akses menu dibagikan sesuai peran', function (string $role, bool $expected) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can.manageStaff', $expected)
            ->where('auth.can.viewActivityLog', $expected),
        );
})->with([
    'Pengelola' => ['manager', true],
    'Admin' => ['admin', false],
]);
