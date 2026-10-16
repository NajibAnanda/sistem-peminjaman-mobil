<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PhotoSource;
use App\Enums\PhotoStage;
use Database\Factories\BookingPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Foto bukti serah terima. Disimpan di disk privat dan hanya disajikan lewat
 * BookingPhotoController (Policy). Tidak pernah dihapus.
 *
 * @property int $id
 * @property int $booking_id
 * @property PhotoStage $stage
 * @property PhotoSource $source
 * @property string $path
 * @property Carbon $taken_at
 * @property int $uploaded_by
 */
#[Fillable(['booking_id', 'stage', 'source', 'path', 'taken_at', 'uploaded_by'])]
final class BookingPhoto extends Model
{
    /** @use HasFactory<BookingPhotoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => PhotoStage::class,
            'source' => PhotoSource::class,
            'taken_at' => 'datetime',
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
