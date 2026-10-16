<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalisasi no HP Indonesia ke format 08xx (AGENTS.md 5.17).
 */
final class PhoneNumber
{
    public const MIN_DIGITS = 10;

    public const MAX_DIGITS = 15;

    /**
     * Menerima 08xx atau +62xx (spasi, titik, tanda hubung, dan kurung
     * diabaikan). Mengembalikan null bila formatnya tidak dikenali; panjang
     * diperiksa terpisah setelah normalisasi.
     */
    public static function normalize(string $value): ?string
    {
        $compact = (string) preg_replace('/[\s.\-()]/', '', $value);

        if (preg_match('/^\+628\d+$/', $compact) === 1) {
            return '0'.substr($compact, 3);
        }

        if (preg_match('/^08\d+$/', $compact) === 1) {
            return $compact;
        }

        return null;
    }
}
