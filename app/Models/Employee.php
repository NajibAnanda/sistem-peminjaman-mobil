<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Support\SearchTerm;
use App\Support\TableQuery;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $division
 * @property string $phone
 * @property bool $is_active
 */
#[Fillable(['name', 'division', 'phone', 'is_active'])]
final class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Pencarian dan filter tabel Data Pegawai (pola tabel standar).
     *
     * @param  Builder<self>  $query
     */
    public function scopeForTable(Builder $query, TableQuery $table): void
    {
        $query
            ->when($table->search, function (Builder $query, string $term): void {
                $escaped = SearchTerm::escape($term);

                $query->where(
                    fn (Builder $query) => $query
                        ->where('name', 'like', "%{$escaped}%")
                        ->orWhere('phone', 'like', "%{$escaped}%")
                        ->orWhere('division', 'like', "%{$escaped}%"),
                );
            })
            ->when($table->filter('division'), fn (Builder $query, array $divisions) => $query->whereIn('division', $divisions))
            ->when($table->filter('status'), fn (Builder $query, array $statuses) => $query->whereIn(
                'is_active',
                array_map(fn (string $status): bool => ActiveStatus::from($status)->isActive(), $statuses),
            ));
    }
}
