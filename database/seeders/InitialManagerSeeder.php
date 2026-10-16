<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

final class InitialManagerSeeder extends Seeder
{
    /**
     * Membuat akun Pengelola pertama dari .env. Aman dijalankan ulang: akun
     * yang sudah ada tidak diubah, sehingga password yang sudah diganti tetap.
     */
    public function run(): void
    {
        $email = config('fleet.initial_manager.email');
        $password = config('fleet.initial_manager.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException('Isi FLEET_MANAGER_EMAIL dan FLEET_MANAGER_PASSWORD di file .env sebelum menjalankan seeder.');
        }

        User::query()->firstOrCreate(
            ['email' => mb_strtolower($email)],
            [
                'name' => (string) config('fleet.initial_manager.name'),
                'password' => $password,
                'role' => StaffRole::Manager,
                'is_active' => true,
                'must_change_password' => true,
            ],
        );
    }
}
