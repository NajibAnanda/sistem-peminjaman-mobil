<?php

use App\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\InitialManagerSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config([
        'fleet.initial_manager.name' => 'Kepala GA',
        'fleet.initial_manager.email' => 'Kepala.GA@Kantor.test',
        'fleet.initial_manager.password' => 'Sementara2026',
    ]);
});

test('seeder membuat Pengelola pertama yang wajib ganti password', function () {
    $this->seed(InitialManagerSeeder::class);

    $manager = User::query()->sole();

    expect($manager->email)->toBe('kepala.ga@kantor.test')
        ->and($manager->name)->toBe('Kepala GA')
        ->and($manager->role)->toBe(StaffRole::Manager)
        ->and($manager->is_active)->toBeTrue()
        ->and($manager->must_change_password)->toBeTrue()
        ->and(Hash::check('Sementara2026', $manager->password))->toBeTrue();
});

test('seeder aman dijalankan ulang tanpa menimpa password yang sudah diganti', function () {
    $this->seed(InitialManagerSeeder::class);

    User::query()->sole()->forceFill([
        'password' => 'SudahDiganti2026',
        'must_change_password' => false,
    ])->save();

    $this->seed(InitialManagerSeeder::class);

    $manager = User::query()->sole();

    expect(Hash::check('SudahDiganti2026', $manager->password))->toBeTrue()
        ->and($manager->must_change_password)->toBeFalse();
});

test('seeder gagal dengan pesan jelas bila kredensial belum diisi', function () {
    config(['fleet.initial_manager.email' => null]);

    $this->seed(InitialManagerSeeder::class);
})->throws(RuntimeException::class, 'FLEET_MANAGER_EMAIL');
