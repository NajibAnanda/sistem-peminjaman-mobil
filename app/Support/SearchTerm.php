<?php

declare(strict_types=1);

namespace App\Support;

final class SearchTerm
{
    /**
     * Meng-escape karakter khusus SQL LIKE (% dan _) serta backslash (\)
     * agar query pencarian mencocokkan karakter harfiah, bukan wildcard.
     */
    public static function escape(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }
}
