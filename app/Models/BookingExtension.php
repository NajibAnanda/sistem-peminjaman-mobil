<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BookingExtensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Riwayat perpanjangan (FR-18). Hanya punya created_at.
 *
 * @property int $id
 * @property int $booking_id
 * @property Carbon $previous_planned_return_at
 * @property Carbon $new_planned_return_at
 * @property string $reason
 * @property int $extended_by
 * @property Carbon $created_at
 */
#[Fillable(['booking_id', 'previous_planned_return_at', 'new_planned_return_at', 'reason', 'extended_by'])]
final class BookingExtension extends Model
{
    /** @use HasFactory<BookingExtensionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_planned_return_at' => 'datetime',
            'new_planned_return_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function extender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by');
    }
}
