<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingDisplayStatus;
use App\Enums\BookingStatus;
use App\Support\TableQuery;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int $employee_id
 * @property int $car_id
 * @property string $purpose
 * @property Carbon $start_at
 * @property Carbon $planned_return_at
 * @property Carbon|null $picked_up_at
 * @property Carbon|null $returned_at
 * @property BookingStatus $status
 * @property string|null $return_notes
 * @property string|null $cancellation_reason
 * @property Carbon|null $cancelled_at
 * @property int $created_by
 * @property Carbon $created_at
 */
#[Fillable([
    'code', 'employee_id', 'car_id', 'purpose', 'start_at', 'planned_return_at',
    'picked_up_at', 'returned_at', 'status', 'return_notes', 'cancellation_reason',
    'cancelled_at', 'created_by', 'picked_up_by', 'returned_by', 'cancelled_by',
])]
final class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'start_at' => 'datetime',
            'planned_return_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Car, $this>
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pickedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return HasMany<BookingPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(BookingPhoto::class);
    }

    /**
     * @return HasMany<BookingExtension, $this>
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(BookingExtension::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Peminjaman yang masih berjalan (Dijadwalkan atau Aktif).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOngoing(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::ongoing());
    }

    /**
     * Peminjaman berjalan yang rentangnya tumpang tindih dengan [$start, $end)
     * (AGENTS.md 5.3): baru.start < lama.akhir DAN baru.akhir > lama.start.
     * Untuk peminjaman Aktif yang terlambat, lama.akhir = sekarang karena mobil
     * dianggap terpakai sampai benar-benar dikembalikan. Batas yang tepat
     * bersentuhan tidak dianggap bentrok.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        $query->ongoing()
            ->where('start_at', '<', $end)
            ->where(function (Builder $query) use ($start, $now): void {
                $query->where('planned_return_at', '>', $start);

                // Akhir peminjaman terlambat = sekarang, jadi bentrok hanya bila
                // jadwal baru dimulai sebelum sekarang.
                if ($now->greaterThan($start)) {
                    $query->orWhere(fn (Builder $query) => $query
                        ->where('status', BookingStatus::Active)
                        ->where('planned_return_at', '<', $now));
                }
            });
    }

    /**
     * Terlambat: Aktif dan rencana kembali sudah lewat (AGENTS.md 5.2).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->where('status', BookingStatus::Active)->where('planned_return_at', '<', $now ?? now());
    }

    /**
     * Belum Diambil: Dijadwalkan dan waktu mulai sudah lewat (AGENTS.md 5.2).
     *
     * @param  Builder<self>  $query
     */
    public function scopeNotPickedUp(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->where('status', BookingStatus::Scheduled)->where('start_at', '<', $now ?? now());
    }

    /**
     * Selesai terlambat: dikembalikan setelah rencana kembali (AGENTS.md 5.2).
     *
     * @param  Builder<self>  $query
     */
    public function scopeCompletedLate(Builder $query): void
    {
        $query->where('status', BookingStatus::Completed)->whereColumn('returned_at', '>', 'planned_return_at');
    }

    /**
     * Filter berdasarkan status tampilan (termasuk indikator turunan).
     *
     * @param  Builder<self>  $query
     * @param  list<string>  $statuses  nilai BookingDisplayStatus
     */
    public function scopeWhereDisplayStatus(Builder $query, array $statuses): void
    {
        $now = now();

        $query->where(function (Builder $query) use ($statuses, $now): void {
            foreach ($statuses as $status) {
                $query->orWhere(fn (Builder $query) => match (BookingDisplayStatus::from($status)) {
                    BookingDisplayStatus::Scheduled => $query->where('status', BookingStatus::Scheduled)->where('start_at', '>=', $now),
                    BookingDisplayStatus::NotPickedUp => $query->notPickedUp($now),
                    BookingDisplayStatus::Active => $query->where('status', BookingStatus::Active)->where('planned_return_at', '>=', $now),
                    BookingDisplayStatus::Overdue => $query->overdue($now),
                    BookingDisplayStatus::Completed => $query->where('status', BookingStatus::Completed),
                    BookingDisplayStatus::Cancelled => $query->where('status', BookingStatus::Cancelled),
                });
            }
        });
    }

    /**
     * Pencarian dan filter tabel Peminjaman (pola tabel standar).
     *
     * @param  Builder<self>  $query
     */
    public function scopeForTable(Builder $query, TableQuery $table): void
    {
        $query
            ->when($table->search, fn (Builder $query, string $term) => $query->where(
                fn (Builder $query) => $query
                    ->where('code', 'like', "%{$term}%")
                    ->orWhereHas('employee', fn (Builder $query) => $query
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('employee_number', 'like', "%{$term}%"))
                    ->orWhereHas('car', fn (Builder $query) => $query->where('plate_number', 'like', "%{$term}%")),
            ))
            ->when($table->filter('status'), fn (Builder $query, array $statuses) => $query->whereDisplayStatus($statuses));
    }

    public function displayStatus(?CarbonInterface $now = null): BookingDisplayStatus
    {
        return BookingDisplayStatus::resolve($this->status, $this->start_at, $this->planned_return_at, $now ?? now());
    }
}
