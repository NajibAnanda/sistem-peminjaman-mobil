<?php

use App\Models\User;

test('Pengelola tanpa 2FA boleh memakai aplikasi bila 2FA tidak diwajibkan', function () {
    config(['fleet.security.require_two_factor_for_manager' => false]);

    $this->actingAs(User::factory()->manager()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

describe('saat 2FA diwajibkan untuk Pengelola', function () {
    beforeEach(function () {
        config(['fleet.security.require_two_factor_for_manager' => true]);
    });

    test('Pengelola tanpa 2FA diarahkan ke halaman keamanan', function () {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('security.edit'));
    });

    test('Pengelola tanpa 2FA tetap bisa membuka halaman keamanan', function () {
        $this->actingAs(User::factory()->manager()->create())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk();
    });

    test('Pengelola tanpa 2FA tetap bisa mengaktifkan 2FA dan keluar', function () {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->from(route('security.edit'))
            ->post(route('two-factor.enable'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('security.edit'));

        expect($manager->fresh()->two_factor_secret)->not->toBeNull();

        $this->post(route('logout'))->assertRedirect(route('home'));
    });

    test('Pengelola dengan 2FA aktif tidak diarahkan', function () {
        $this->actingAs(User::factory()->manager()->withTwoFactor()->create())
            ->get(route('dashboard'))
            ->assertOk();
    });

    test('Admin tanpa 2FA tidak terkena kewajiban', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk();
    });
});
