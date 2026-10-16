<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Nonce dibuat sebelum view dirender agar tag @vite dan skrip inline
        // di app.blade.php mendapat atribut nonce yang sama dengan header CSP.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $devServer = $this->viteDevServer();
        $dev = $devServer === null ? [] : [$devServer];
        $devSocket = $devServer === null ? [] : [preg_replace('/^http/', 'ws', $devServer)];

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$dev],
            // Sonner, progress bar Inertia, dan Vite (dev) menyisipkan <style>
            // lewat JavaScript, sehingga gaya inline harus diizinkan.
            'style-src' => ["'self'", "'unsafe-inline'", ...$dev],
            'img-src' => ["'self'", 'data:', 'blob:', ...$dev],
            'font-src' => ["'self'", 'data:', ...$dev],
            'connect-src' => ["'self'", ...$dev, ...$devSocket],
            'media-src' => ["'self'", 'blob:'],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', $sources))
            ->implode('; ');
    }

    /**
     * Alamat server Vite saat `npm run dev` berjalan; null pada build produksi.
     */
    private function viteDevServer(): ?string
    {
        if (! Vite::isRunningHot()) {
            return null;
        }

        $url = trim((string) file_get_contents(Vite::hotFile()));

        return $url === '' ? null : rtrim($url, '/');
    }
}
