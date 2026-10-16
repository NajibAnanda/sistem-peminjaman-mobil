<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Plat nomor disimpan dalam satu bentuk baku (huruf besar, satu spasi antar
 * bagian) agar keunikan tidak terkecoh beda penulisan: "b1234abc",
 * "B 1234  ABC", dan "B-1234-ABC" semuanya menjadi "B 1234 ABC".
 */
final class PlateNumber
{
    public static function normalize(string $value): string
    {
        $upper = mb_strtoupper(trim($value));
        $spaced = (string) preg_replace('/[\s\-.]+/', ' ', $upper);

        // Sisipkan spasi di pergantian huruf/angka bila ditulis rapat.
        return (string) preg_replace('/(?<=[A-Z])(?=\d)|(?<=\d)(?=[A-Z])/', ' ', $spaced);
    }
}
