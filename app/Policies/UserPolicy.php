<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Kelola Petugas hanya untuk Pengelola (prd.md section 9). Aturan seperti
 * "tidak boleh menonaktifkan diri sendiri" dan "minimal 1 Pengelola aktif"
 * adalah aturan bisnis dan dijaga di Action.
 */
final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isManager();
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, User $staff): bool
    {
        return $user->isManager();
    }

    public function deactivate(User $user, User $staff): bool
    {
        return $user->isManager();
    }

    public function activate(User $user, User $staff): bool
    {
        return $user->isManager();
    }

    public function resetPassword(User $user, User $staff): bool
    {
        return $user->isManager();
    }
}
