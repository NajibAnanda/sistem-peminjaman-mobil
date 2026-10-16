<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;

/**
 * Baris Riwayat. Membutuhkan relasi `employee`, `car`, dan withCount('extensions').
 *
 * @mixin Booking
 */
final class HistoryResource extends BookingResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'extensions_count' => (int) $this->extensions_count,
            'lateness_minutes' => $this->resource->latenessMinutes(),
        ];
    }
}
