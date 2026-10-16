<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Car;
use App\Models\User;

/**
 * Data Mobil boleh dikelola Pengelola dan Admin (prd.md section 9). Akun
 * nonaktif sudah dikeluarkan oleh middleware sebelum sampai ke sini.
 */
final class CarPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Car $car): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Car $car): bool
    {
        return true;
    }

    public function changeOperationalStatus(User $user, Car $car): bool
    {
        return true;
    }

    public function updatePhoto(User $user, Car $car): bool
    {
        return true;
    }

    public function deactivate(User $user, Car $car): bool
    {
        return true;
    }

    public function activate(User $user, Car $car): bool
    {
        return true;
    }
}
