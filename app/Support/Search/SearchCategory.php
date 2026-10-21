<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\User;

/**
 * Satu kategori di pencarian global. Untuk menambah kategori baru, buat kelas
 * yang mengimplementasikan interface ini lalu daftarkan di GlobalSearch.
 */
interface SearchCategory
{
    /** Kunci stabil untuk frontend, mis. "cars". */
    public function key(): string;

    /** Judul grup hasil, Bahasa Indonesia. */
    public function label(): string;

    /** Hak akses wajib lewat Policy, bukan hanya disembunyikan di UI. */
    public function allows(User $user): bool;

    /**
     * Hanya field yang dibutuhkan untuk menampilkan hasil.
     *
     * @return list<array{id: int, title: string, subtitle: string, url: string}>
     */
    public function search(string $term, int $limit): array;
}
