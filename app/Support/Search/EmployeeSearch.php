<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Data karyawan tidak punya halaman detail, jadi hasil membuka daftar
 * karyawan dengan pencarian nama itu.
 */
final class EmployeeSearch implements SearchCategory
{
    public function key(): string
    {
        return 'employees';
    }

    public function label(): string
    {
        return 'Karyawan';
    }

    public function allows(User $user): bool
    {
        return $user->can('viewAny', Employee::class);
    }

    public function search(string $term, int $limit): array
    {
        return array_values(Employee::query()
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'division', 'phone', 'is_active'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'title' => $employee->name,
                'subtitle' => "{$employee->division} · {$employee->phone}".($employee->is_active ? '' : ' · Nonaktif'),
                'url' => route('employees.index', ['search' => $employee->name]),
            ])
            ->all());
    }
}
