<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CarPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $car_id
 * @property int $position
 * @property string $path
 * @property Carbon $updated_at
 */
#[Fillable(['car_id', 'position', 'path'])]
final class CarPhoto extends Model
{
    /** @use HasFactory<CarPhotoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Car, $this>
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
