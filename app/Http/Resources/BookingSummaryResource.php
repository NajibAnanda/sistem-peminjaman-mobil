<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ringkasan peminjaman untuk ditampilkan di halaman lain (mis. detail mobil).
 * Membutuhkan relasi `employee`.
 *
 * @mixin Booking
 */
final class BookingSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'employee_name' => $this->employee->name,
            'purpose' => $this->purpose,
            'start_at' => $this->start_at->toIso8601String(),
            'planned_return_at' => $this->planned_return_at->toIso8601String(),
        ];
    }
}
