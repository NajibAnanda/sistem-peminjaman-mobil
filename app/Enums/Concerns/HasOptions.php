<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Untuk backed enum yang memiliki method label(): menghasilkan pilihan
 * {value, label} untuk dropdown dan filter tabel di frontend.
 */
trait HasOptions
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => (string) $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => (string) $case->value, self::cases());
    }
}
