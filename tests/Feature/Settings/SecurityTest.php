<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('halaman keamanan menampilkan pengaturan 2FA tanpa passkey', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/security')
            ->where('canManageTwoFactor', true)
            ->where('twoFactorEnabled', false)
            ->where('twoFactorRequired', false)
            ->missing('canManagePasskeys')
            ->missing('passkeys'),
        );
});

test('halaman keamanan memberi tahu bila 2FA wajib bagi Pengelola', function () {
    config(['fleet.security.require_two_factor_for_manager' => true]);

    $this->actingAs(User::factory()->manager()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('twoFactorRequired', true));
});

test('halaman keamanan meminta konfirmasi password', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('security.edit'))
        ->assertRedirect(route('password.confirm'));
});

test('password dapat diganti dari halaman keamanan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'GarasiKantor7',
            'password_confirmation' => 'GarasiKantor7',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('GarasiKantor7', $user->refresh()->password))->toBeTrue();
});

test('ganti password ditolak bila password saat ini salah', function () {
    $this->actingAs(User::factory()->create())
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password-salah',
            'password' => 'GarasiKantor7',
            'password_confirmation' => 'GarasiKantor7',
        ])
        ->assertSessionHasErrors(['current_password' => 'Password salah.'])
        ->assertRedirect(route('security.edit'));
});
