<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('halaman profil dapat dibuka', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/profile'));
});

test('nama dan email profil dapat diperbarui', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@kantor.test',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Budi Santoso')
        ->and($user->email)->toBe('budi@kantor.test');
});

test('email yang sudah dipakai akun lain ditolak', function () {
    User::factory()->create(['email' => 'terpakai@kantor.test']);

    $this->actingAs(User::factory()->create())
        ->patch(route('profile.update'), [
            'name' => 'Budi',
            'email' => 'terpakai@kantor.test',
        ])
        ->assertSessionHasErrors(['email' => 'Email sudah dipakai.']);
});
