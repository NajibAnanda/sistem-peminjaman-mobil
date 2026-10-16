<?php

use App\Enums\ActivityEvent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('perintah artisan mereset password Pengelola yang terkunci', function () {
    $manager = User::factory()->manager()->create(['email' => 'kepala@kantor.test']);

    $this->artisan('fleet:reset-manager-password', ['email' => 'Kepala@Kantor.test'])
        ->expectsConfirmation('Reset password akun ini? Semua sesi akun yang sedang login akan diakhiri.', 'yes')
        ->expectsOutputToContain('Password sementara:')
        ->assertSuccessful();

    $manager->refresh();

    expect($manager->must_change_password)->toBeTrue()
        ->and(Hash::check('password', $manager->password))->toBeFalse()
        ->and(lastActivity()->event)->toBe(ActivityEvent::PasswordReset->value)
        ->and(lastActivity()->causer_id)->toBeNull()
        ->and(lastActivity()->properties['via'])->toBe('artisan');
});

test('perintah artisan dapat mengaktifkan kembali Pengelola nonaktif', function () {
    $manager = User::factory()->manager()->inactive()->create(['email' => 'kepala@kantor.test']);

    $this->artisan('fleet:reset-manager-password', ['email' => 'kepala@kantor.test'])
        ->expectsConfirmation('Reset password akun ini? Semua sesi akun yang sedang login akan diakhiri.', 'yes')
        ->expectsConfirmation('Akun ini nonaktif. Aktifkan kembali sekaligus?', 'yes')
        ->assertSuccessful();

    expect($manager->fresh()->is_active)->toBeTrue();
});

test('perintah artisan tidak mengubah apa pun bila dibatalkan', function () {
    $manager = User::factory()->manager()->create(['email' => 'kepala@kantor.test']);

    $this->artisan('fleet:reset-manager-password', ['email' => 'kepala@kantor.test'])
        ->expectsConfirmation('Reset password akun ini? Semua sesi akun yang sedang login akan diakhiri.', 'no')
        ->assertFailed();

    expect(Hash::check('password', $manager->fresh()->password))->toBeTrue();
});

test('perintah artisan hanya berlaku untuk akun Pengelola', function () {
    User::factory()->admin()->create(['email' => 'admin@kantor.test']);

    $this->artisan('fleet:reset-manager-password', ['email' => 'admin@kantor.test'])
        ->expectsOutputToContain('tidak ditemukan')
        ->assertFailed();
});
