<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Pencarian dan filter tabel dari query string (?search=&role[]=&status[]=),
 * ditambah filter periode opsional (?from=&to=, format Y-m-d).
 *
 * Satu-satunya cara membaca parameter tabel di server. Nilai filter di luar
 * daftar yang diizinkan diabaikan (bukan error), sehingga URL lama atau
 * yang diubah manual tetap aman dibuka.
 */
final readonly class TableQuery
{
    private const MAX_SEARCH_LENGTH = 100;

    private const DATE_PATTERN = '/^(\d{4})-(\d{2})-(\d{2})$/';

    /**
     * @param  array<string, list<string>>  $filters
     */
    private function __construct(
        public ?string $search,
        public array $filters,
        public bool $hasPeriod = false,
        /** Awal hari pertama periode (inklusif). */
        public ?CarbonImmutable $from = null,
        /** Akhir hari terakhir periode (inklusif). */
        public ?CarbonImmutable $to = null,
        /** Periode bawaan bila URL tidak membawa periode (mis. bulan berjalan). */
        public bool $defaultsToCurrentMonth = false,
    ) {}

    /**
     * @param  array<string, list<string>>  $allowedFilters  kunci filter => nilai yang diizinkan
     * @param  bool  $withPeriod  baca juga filter periode `from` & `to`
     * @param  bool  $defaultToCurrentMonth  periode wajib: bila URL tidak membawa
     *                                       `from`/`to`, pakai bulan berjalan
     */
    public static function fromRequest(Request $request, array $allowedFilters, bool $withPeriod = false, bool $defaultToCurrentMonth = false): self
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

        $from = $withPeriod ? self::date($request->query('from')) : null;
        $to = $withPeriod ? self::date($request->query('to')) : null;

        $defaultsToCurrentMonth = $withPeriod && $defaultToCurrentMonth;

        if ($defaultsToCurrentMonth && ! $request->query->has('from') && ! $request->query->has('to')) {
            [$from, $to] = self::currentMonth();
        }

        // Rentang terbalik dianggap salah ketik, bukan hasil kosong.
        if ($from !== null && $to !== null && $from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return new self(
            $search === '' ? null : $search,
            $filters,
            $withPeriod,
            $from?->startOfDay(),
            $to?->endOfDay(),
            $defaultsToCurrentMonth,
        );
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
     * @return array{search: string|null, filters: array<string, list<string>>, period?: array{from: string|null, to: string|null, default?: array{from: string, to: string}}}
     */
    public function toArray(): array
    {
        $state = [
            'search' => $this->search,
            'filters' => $this->filters,
        ];

        if ($this->hasPeriod) {
            $state['period'] = [
                'from' => $this->from?->toDateString(),
                'to' => $this->to?->toDateString(),
            ];

            // Frontend memakai periode bawaan untuk tombol reset dan untuk
            // menentukan apakah tabel sedang "difilter".
            if ($this->defaultsToCurrentMonth) {
                [$from, $to] = self::currentMonth();
                $state['period']['default'] = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
            }
        }

        return $state;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function currentMonth(): array
    {
        $now = CarbonImmutable::now();

        return [$now->startOfMonth(), $now->endOfMonth()];
    }

    /**
     * Tanggal yang tidak ada (mis. 2026-02-31) diabaikan, bukan digeser.
     */
    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match(self::DATE_PATTERN, $value, $parts) !== 1
            || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            return null;
        }

        return CarbonImmutable::create((int) $parts[1], (int) $parts[2], (int) $parts[3], 0, 0, 0, (string) config('app.timezone'));
    }
}
