<?php

use App\Enums\ActivityEvent;
use App\Enums\StaffRole;
use App\Models\User;

test('perintah artisan membuat akun Pengelola darurat baru', function () {
    $this->artisan('fleet:create-emergency-manager', [
        '--name' => 'Pengelola Darurat Kantor',
        '--email' => 'darurat@kantor.test',
    ])
        ->expectsConfirmation('Apakah Anda yakin ingin membuat akun Pengelola darurat ini?', 'yes')
        ->expectsOutputToContain('Akun Pengelola darurat berhasil dibuat!')
        ->assertSuccessful();

    $manager = User::query()->where('email', 'darurat@kantor.test')->first();

    expect($manager)->not->toBeNull()
        ->and($manager->name)->toBe('Pengelola Darurat Kantor')
        ->and($manager->role)->toBe(StaffRole::Manager)
        ->and($manager->is_active)->toBeTrue()
        ->and($manager->must_change_password)->toBeTrue()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Created->value)
        ->and(lastActivity()->causer_id)->toBeNull()
        ->and(lastActivity()->properties['via'])->toBe('artisan');
});

test('perintah artisan menolak email yang sudah terdaftar', function () {
    User::factory()->create(['email' => 'terdaftar@kantor.test']);

    $this->artisan('fleet:create-emergency-manager', [
        '--name' => 'Pengelola Duplikat',
        '--email' => 'terdaftar@kantor.test',
    ])
        ->expectsOutputToContain('sudah digunakan oleh akun lain')
        ->assertFailed();
});

test('perintah artisan menolak format email tidak valid', function () {
    $this->artisan('fleet:create-emergency-manager', [
        '--name' => 'Pengelola Salah',
        '--email' => 'bukan-email',
    ])
        ->expectsOutputToContain('Alamat email tidak valid.')
        ->assertFailed();
});

test('perintah artisan tidak membuat akun bila dibatalkan', function () {
    $this->artisan('fleet:create-emergency-manager', [
        '--name' => 'Pengelola Batal',
        '--email' => 'batal@kantor.test',
    ])
        ->expectsConfirmation('Apakah Anda yakin ingin membuat akun Pengelola darurat ini?', 'no')
        ->assertFailed();

    expect(User::query()->where('email', 'batal@kantor.test')->exists())->toBeFalse();
});
