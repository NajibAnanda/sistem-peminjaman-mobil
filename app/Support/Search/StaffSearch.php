<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\User;
use App\Support\SearchTerm;
use Illuminate\Database\Eloquent\Builder;

/**
 * Akun petugas (khusus Pengelola). Kelola Petugas tidak punya halaman detail,
 * jadi hasil membuka daftar petugas dengan pencarian email itu.
 */
final class StaffSearch implements SearchCategory
{
    public function key(): string
    {
        return 'staff';
    }

    public function label(): string
    {
        return 'Petugas';
    }

    public function allows(User $user): bool
    {
        return $user->can('viewAny', User::class);
    }

    public function search(string $term, int $limit): array
    {
        $escaped = SearchTerm::escape($term);

        return array_values(User::query()
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$escaped}%")
                ->orWhere('email', 'like', "%{$escaped}%"))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'role', 'is_active'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'title' => $user->name,
                'subtitle' => $user->email.' · '.$user->role->label().($user->is_active ? '' : ' · Nonaktif'),
                'url' => route('staff.index', ['search' => $user->email], absolute: false),
            ])
            ->all());
    }
}
