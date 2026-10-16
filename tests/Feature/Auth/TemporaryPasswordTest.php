<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('petugas dengan password sementara diarahkan ke halaman ganti password', function (string $routeName) {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertRedirect(route('password.change'));
})->with(['dashboard', 'profile.edit', 'bookings.index']);

test('halaman ganti password dapat dibuka', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)
        ->get(route('password.change'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/change-password')
            ->has('passwordRules'),
        );
});

test('password sementara dapat diganti dan kewajibannya selesai', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)
        ->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => 'RahasiaBaru2026',
            'password_confirmation' => 'RahasiaBaru2026',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $user->refresh();

    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('RahasiaBaru2026', $user->password))->toBeTrue();

    $this->get(route('dashboard'))->assertOk();
});

test('ganti password sementara ditolak bila password saat ini salah', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)
        ->put(route('password.change.update'), [
            'current_password' => 'salah-password',
            'password' => 'RahasiaBaru2026',
            'password_confirmation' => 'RahasiaBaru2026',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});

test('password baru tidak boleh sama dengan password sementara', function () {
    $user = User::factory()->mustChangePassword()->create([
        'password' => 'Sementara2026',
    ]);

    $this->actingAs($user)
        ->put(route('password.change.update'), [
            'current_password' => 'Sementara2026',
            'password' => 'Sementara2026',
            'password_confirmation' => 'Sementara2026',
        ])
        ->assertSessionHasErrors(['password' => 'Password baru harus berbeda dari password saat ini.']);
});

test('petugas dengan password sementara tetap bisa keluar', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
});
