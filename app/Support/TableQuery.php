<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Pencarian dan filter tabel dari query string (?search=&role[]=&status[]=).
 *
 * Satu-satunya cara membaca parameter tabel di server. Nilai filter di luar
 * daftar yang diizinkan diabaikan (bukan error), sehingga URL lama atau
 * yang diubah manual tetap aman dibuka.
 */
final readonly class TableQuery
{
    private const MAX_SEARCH_LENGTH = 100;

    /**
     * @param  array<string, list<string>>  $filters
     */
    private function __construct(
        public ?string $search,
        public array $filters,
    ) {}

    /**
     * @param  array<string, list<string>>  $allowedFilters  kunci filter => nilai yang diizinkan
     */
    public static function fromRequest(Request $request, array $allowedFilters): self
    {
        $search = $request->query('search');
        $search = is_string($search) ? mb_substr(trim($search), 0, self::MAX_SEARCH_LENGTH) : '';

        $filters = [];

        foreach ($allowedFilters as $key => $allowedValues) {
            $raw = $request->query($key);
            $raw = is_array($raw) ? $raw : [$raw];

            $filters[$key] = array_values(array_unique(array_filter(
                $raw,
                fn (mixed $value): bool => is_string($value) && in_array($value, $allowedValues, true),
            )));
        }

        return new self($search === '' ? null : $search, $filters);
    }

    /**
     * @return list<string>
     */
    public function filter(string $key): array
    {
        return $this->filters[$key] ?? [];
    }

    /**
     * Dikirim ke frontend agar input pencarian dan centang filter sesuai URL.
     *
     * @return array{search: string|null, filters: array<string, list<string>>}
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'filters' => $this->filters,
        ];
    }
}
