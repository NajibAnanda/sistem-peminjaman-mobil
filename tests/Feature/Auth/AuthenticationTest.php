<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

test('halaman login dapat dibuka tanpa opsi lupa password', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/login')
            ->missing('canResetPassword'),
        );
});

test('petugas aktif dapat login dan waktu login terakhir tercatat', function () {
    $this->travelTo(now()->setDateTime(2026, 10, 16, 8, 0));
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at->equalTo(now()))->toBeTrue();
});

test('petugas dengan 2FA diarahkan ke tantangan 2FA', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHas('login.id', $user->id);

    $this->assertGuest();
});

test('password salah ditolak dengan pesan Bahasa Indonesia', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password-salah',
    ])->assertSessionHasErrors(['email' => 'Email atau password salah.']);

    $this->assertGuest();
});

test('akun nonaktif tidak dapat login walau password benar', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => __('auth.inactive')]);

    $this->assertGuest();
    expect($user->fresh()->last_login_at)->toBeNull();
});

test('akun nonaktif dengan password salah hanya mendapat pesan umum', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password-salah',
    ])->assertSessionHasErrors(['email' => 'Email atau password salah.']);
});

test('petugas dapat keluar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('login dibatasi 5 percobaan per menit per email dan IP', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password-salah',
    ])->assertTooManyRequests();
});
