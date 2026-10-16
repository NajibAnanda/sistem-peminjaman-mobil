<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePasswordChanged
{
    /**
     * Route yang tetap boleh dibuka selama password sementara belum diganti.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
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

        if ($user instanceof User && $user->must_change_password && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
