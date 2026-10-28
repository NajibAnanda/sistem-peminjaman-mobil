<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\CarDisplayStatus;
use App\Enums\CarOperationalStatus;
use App\Support\SearchTerm;
use App\Support\TableQuery;
use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $plate_number
 * @property string $brand
 * @property string $model
 * @property CarOperationalStatus $operational_status
 * @property bool $is_active
 * @property bool $has_active_booking diisi oleh scope withDisplayStatus()
 * @property int $photos_count
 * @property Carbon $created_at
 */
#[Fillable(['plate_number', 'brand', 'model', 'operational_status', 'is_active'])]
final class Car extends Model
{
    /** @use HasFactory<CarFactory> */
    use HasFactory;

    /** Slot foto referensi: 1 = depan/samping, 2 = belakang/interior. */
    public const PHOTO_POSITIONS = [1, 2];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operational_status' => CarOperationalStatus::class,
            'is_active' => 'boolean',
            'has_active_booking' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CarPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(CarPhoto::class)->orderBy('position');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasOne<Booking, $this>
     */
    public function activeBooking(): HasOne
    {
        return $this->hasOne(Booking::class)->where('status', BookingStatus::Active);
    }

    /**
     * Jadwal yang belum diambil; ditampilkan sebagai peringatan sebelum mobil
     * diubah ke Maintenance (FR-07).
     *
     * @return HasMany<Booking, $this>
     */
    public function scheduledBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->where('status', BookingStatus::Scheduled)
            ->orderBy('start_at');
    }

    /**
     * Menyertakan flag peminjaman aktif dalam satu query agar status tampilan
     * dapat dihitung tanpa N+1.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithDisplayStatus(Builder $query): void
    {
        $query->withExists([
            'bookings as has_active_booking' => fn (Builder $query) => $query->where('status', BookingStatus::Active),
        ]);
    }

    /**
     * Filter berdasarkan status tampilan (diturunkan, tidak disimpan).
     *
     * @param  Builder<self>  $query
     * @param  list<string>  $statuses  nilai CarDisplayStatus
     */
    public function scopeWhereDisplayStatus(Builder $query, array $statuses): void
    {
        $hasActiveBooking = fn (Builder $query) => $query->where('status', BookingStatus::Active);

        $query->where(function (Builder $query) use ($statuses, $hasActiveBooking): void {
            foreach ($statuses as $status) {
                $query->orWhere(fn (Builder $query) => match (CarDisplayStatus::from($status)) {
                    CarDisplayStatus::Inactive => $query->where('is_active', false),
                    CarDisplayStatus::Maintenance => $query->where('is_active', true)
                        ->where('operational_status', CarOperationalStatus::Maintenance),
                    CarDisplayStatus::Borrowed => $query->where('is_active', true)
                        ->where('operational_status', CarOperationalStatus::Operational)
                        ->whereHas('bookings', $hasActiveBooking),
                    CarDisplayStatus::Available => $query->where('is_active', true)
                        ->where('operational_status', CarOperationalStatus::Operational)
                        ->whereDoesntHave('bookings', $hasActiveBooking),
                });
            }
        });
    }

    /**
     * Pencarian dan filter tabel Data Mobil (pola tabel standar).
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
                        ->where('plate_number', 'like', "%{$escaped}%")
                        ->orWhere('brand', 'like', "%{$escaped}%")
                        ->orWhere('model', 'like', "%{$escaped}%"),
                );
            })
            ->when($table->filter('status'), fn (Builder $query, array $statuses) => $query->whereDisplayStatus($statuses));
    }

    /**
     * Membutuhkan scope withDisplayStatus() pada query.
     */
    public function displayStatus(): CarDisplayStatus
    {
        return CarDisplayStatus::resolve($this->is_active, $this->operational_status, $this->has_active_booking);
    }
}
