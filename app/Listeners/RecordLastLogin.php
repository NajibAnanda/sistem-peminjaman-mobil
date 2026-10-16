<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

final class RecordLastLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        // Lewat query builder agar login tidak dianggap perubahan data akun
        // (updated_at tidak ikut berubah).
        User::query()
            ->whereKey($event->user->getKey())
            ->toBase()
            ->update(['last_login_at' => now()]);
    }
}
