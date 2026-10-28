<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\Car;
use App\Models\User;
use App\Support\SearchTerm;
use Illuminate\Database\Eloquent\Builder;

final class CarSearch implements SearchCategory
{
    public function key(): string
    {
        return 'cars';
    }

    public function label(): string
    {
        return 'Mobil';
    }

    public function allows(User $user): bool
    {
        return $user->can('viewAny', Car::class);
    }

    public function search(string $term, int $limit): array
    {
        $escaped = SearchTerm::escape($term);

        return array_values(Car::query()
            ->where(fn (Builder $query) => $query
                ->where('plate_number', 'like', "%{$escaped}%")
                ->orWhere('brand', 'like', "%{$escaped}%")
                ->orWhere('model', 'like', "%{$escaped}%"))
            ->orderBy('plate_number')
            ->limit($limit)
            ->get(['id', 'plate_number', 'brand', 'model', 'is_active'])
            ->map(fn (Car $car): array => [
                'id' => $car->id,
                'title' => "{$car->brand} {$car->model}",
                'subtitle' => $car->plate_number.($car->is_active ? '' : ' · Nonaktif'),
                'url' => route('cars.show', $car, absolute: false),
            ])
            ->all());
    }
}
