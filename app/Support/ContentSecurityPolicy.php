<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Menyusun header Content-Security-Policy. Dipisah dari middleware agar
 * dapat diuji tanpa bergantung pada server Vite yang sedang berjalan.
 */
final class ContentSecurityPolicy
{
    /**
     * @param  string|null  $devServerUrl  alamat server Vite saat `npm run dev`, null pada build produksi
     */
    public static function build(string $nonce, ?string $devServerUrl = null): string
    {
        $dev = [];
        $devSocket = [];

        if ($devServerUrl !== null && ($origin = self::devServerOrigin($devServerUrl)) !== null) {
            $dev = [$origin['http']];
            $devSocket = [$origin['ws']];
        }

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
     * @return array{http: string, ws: string}|null
     */
    private static function devServerOrigin(string $url): ?array
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        // Browser menolak alamat IPv6 (mis. [::1]) sebagai sumber CSP, sehingga
        // CSS dari Vite terblokir. Host diganti wildcard dengan port yang sama;
        // hanya berlaku saat development.
        $host = str_starts_with($parts['host'], '[') ? '*' : $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $socketScheme = $parts['scheme'] === 'https' ? 'wss' : 'ws';

        return [
            'http' => "{$parts['scheme']}://{$host}{$port}",
            'ws' => "{$socketScheme}://{$host}{$port}",
        ];
    }
}
