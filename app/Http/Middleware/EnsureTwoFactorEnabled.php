<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTwoFactorEnabled
{
    /**
     * Route yang dibutuhkan untuk mengaktifkan 2FA atau keluar dari aplikasi.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
        'security.edit',
        'user-password.update',
        'two-factor.*',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'password.change',
        'password.change.update',
        'logout',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user instanceof User
            && $user->isManager()
            && ! $user->hasConfirmedTwoFactor()
            && config('fleet.security.require_two_factor_for_manager')
            && ! $request->routeIs(...self::ALLOWED_ROUTES)
        ) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => 'Aktifkan autentikasi dua faktor terlebih dahulu untuk memakai aplikasi.',
            ]);

            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
