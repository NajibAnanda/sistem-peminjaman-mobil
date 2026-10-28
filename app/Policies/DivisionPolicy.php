<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Data Divisi boleh dikelola Pengelola dan Admin (petugas GA).
 * Akun nonaktif sudah dikeluarkan oleh middleware sebelum sampai ke sini.
 */
final class DivisionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user): bool
    {
        return true;
    }
}
