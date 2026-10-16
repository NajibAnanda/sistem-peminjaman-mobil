<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Car;
use App\Models\CarPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Membutuhkan relasi `photos` dan scope `withDisplayStatus()` pada query.
 *
 * @mixin Car
 */
final class CarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->resource->displayStatus();
        $photos = $this->photos->keyBy('position');

        return [
            'id' => $this->id,
            'plate_number' => $this->plate_number,
            'brand' => $this->brand,
            'model' => $this->model,
            'operational_status' => $this->operational_status->value,
            'is_active' => $this->is_active,
            'display_status' => $status->value,
            'display_status_label' => $status->label(),
            'photos_complete' => $photos->count() === count(Car::PHOTO_POSITIONS),
            'photos' => array_map(
                fn (int $position): array => [
                    'position' => $position,
                    'url' => self::photoUrl($photos->get($position)),
                ],
                Car::PHOTO_POSITIONS,
            ),
        ];
    }

    private static function photoUrl(?CarPhoto $photo): ?string
    {
        if ($photo === null) {
            return null;
        }

        // Versi di URL memaksa browser memuat ulang setelah foto diganti.
        return Storage::disk('public')->url($photo->path).'?v='.$photo->updated_at->timestamp;
    }
}
