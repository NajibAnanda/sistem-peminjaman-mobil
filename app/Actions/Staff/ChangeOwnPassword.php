<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\User;

final class ChangeOwnPassword
{
    public function handle(User $user, string $password): User
    {
        // Mengganti password sendiri sekaligus menuntaskan kewajiban mengganti
        // password sementara yang diberikan Pengelola.
        $user->forceFill([
            'password' => $password,
            'must_change_password' => false,
        ])->save();

        return $user;
    }
}
