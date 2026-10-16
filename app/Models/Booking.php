<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model dasar peminjaman. Aturan transisi, cek bentrok, dan indikator
 * turunan (Terlambat, Belum Diambil) dikerjakan di Fase 4.
 *
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
     * Peminjaman yang masih berjalan (Dijadwalkan atau Aktif).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOngoing(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::ongoing());
    }
}
