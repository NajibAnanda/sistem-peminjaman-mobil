<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\User;
use Illuminate\Contracts\Container\Container;

/**
 * Pencarian global di top bar (Ctrl+K). Kategori yang tidak diizinkan untuk
 * petugas ini dilewati di server, dan grup tanpa hasil tidak dikirim.
 */
final readonly class GlobalSearch
{
    /** Hasil maksimal per kategori. */
    public const LIMIT_PER_CATEGORY = 5;

    /**
     * Urutan tampil di command palette. Kategori baru cukup ditambahkan di sini.
     *
     * @var list<class-string<SearchCategory>>
     */
    private const CATEGORIES = [
        BookingSearch::class,
        CarSearch::class,
        EmployeeSearch::class,
        StaffSearch::class,
    ];

    public function __construct(private Container $container) {}

    /**
     * @return list<array{key: string, label: string, items: list<array{id: int, title: string, subtitle: string, url: string}>}>
     */
    public function run(User $user, string $term): array
    {
        $groups = [];

        foreach (self::CATEGORIES as $class) {
            $category = $this->container->make($class);

            if (! $category->allows($user)) {
                continue;
            }

            $items = $category->search($term, self::LIMIT_PER_CATEGORY);

            if ($items !== []) {
                $groups[] = ['key' => $category->key(), 'label' => $category->label(), 'items' => $items];
            }
        }

        return $groups;
    }
}
