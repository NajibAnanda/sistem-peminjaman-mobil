<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\StaffRole;
use App\Support\SearchTerm;
use App\Support\TableQuery;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property StaffRole $role
 * @property bool $is_active
 * @property bool $must_change_password
 * @property Carbon|null $last_login_at
 * @property Carbon|null $two_factor_confirmed_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'must_change_password', 'last_login_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => StaffRole::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActiveManagers(Builder $query): void
    {
        $query->where('role', StaffRole::Manager)->where('is_active', true);
    }

    /**
     * Pencarian dan filter tabel Kelola Petugas (pola tabel standar).
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
                        ->orWhere('email', 'like', "%{$escaped}%"),
                );
            })
            ->when($table->filter('role'), fn (Builder $query, array $roles) => $query->whereIn('role', $roles))
            ->when($table->filter('status'), fn (Builder $query, array $statuses) => $query->whereIn(
                'is_active',
                array_map(fn (string $status): bool => ActiveStatus::from($status)->isActive(), $statuses),
            ));
    }

    public function isManager(): bool
    {
        return $this->role === StaffRole::Manager;
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }
}
