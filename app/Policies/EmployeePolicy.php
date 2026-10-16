<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Data Karyawan boleh dikelola Pengelola dan Admin (prd.md section 9). Akun
 * nonaktif sudah dikeluarkan oleh middleware sebelum sampai ke sini.
 */
final class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Employee $employee): bool
    {
        return true;
    }

    public function deactivate(User $user, Employee $employee): bool
    {
        return true;
    }

    public function activate(User $user, Employee $employee): bool
    {
        return true;
    }
}
