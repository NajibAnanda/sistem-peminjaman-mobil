<?php

declare(strict_types=1);

namespace App\Support;

final class TemporaryPasswordGenerator
{
    // Tanpa karakter yang mudah tertukar (0/O, 1/l/I) karena password
    // sementara sering didiktekan atau disalin manual oleh petugas.
    private const LETTERS = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';

    private const DIGITS = '23456789';

    public function generate(): string
    {
        $length = max(
            (int) config('fleet.security.temporary_password_length'),
            (int) config('fleet.security.password_min_length'),
        );

        // Satu huruf dan satu angka dijamin ada agar lolos kebijakan password.
        $characters = [$this->pick(self::LETTERS), $this->pick(self::DIGITS)];

        while (count($characters) < $length) {
            $characters[] = $this->pick(self::LETTERS.self::DIGITS);
        }

        for ($i = count($characters) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        return implode('', $characters);
    }

    private function pick(string $pool): string
    {
        return $pool[random_int(0, strlen($pool) - 1)];
    }
}
