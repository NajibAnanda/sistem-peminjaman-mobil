<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SessionTerminator
{
    /**
     * Mengakhiri semua sesi dan token "ingat saya" milik akun.
     *
     * Sesi hanya dapat dihapus langsung bila driver sesi `database`; untuk driver
     * lain, middleware EnsureAccountIsActive tetap mengeluarkan akun nonaktif,
     * dan rotasi remember token mematikan cookie "ingat saya".
     */
    public function forUser(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }
    }
}
