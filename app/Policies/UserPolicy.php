<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    /**
     * Kelola Petugas hanya untuk Pengelola (prd.md section 9).
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager();
    }
}
