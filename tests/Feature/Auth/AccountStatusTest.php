<?php

use App\Models\User;

test('akun yang dinonaktifkan di tengah sesi langsung dikeluarkan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => __('auth.inactive')]);

    $this->assertGuest();
});

test('akun nonaktif tidak dapat membuka halaman pengaturan', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
