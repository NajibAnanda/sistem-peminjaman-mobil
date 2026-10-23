<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Peminjaman untuk daftar. Membutuhkan relasi `employee` dan `car`.
 *
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $displayStatus = $this->resource->displayStatus();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status->value,
            'display_status' => $displayStatus->value,
            'display_status_label' => $displayStatus->label(),
            'purpose' => $this->purpose,
            'start_at' => $this->start_at->toIso8601String(),
            'planned_return_at' => $this->planned_return_at->toIso8601String(),
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            // FR-23: mobil masih dipakai peminjaman Terlambat lain.
            'awaiting_return_code' => $this->resource->awaitingReturnCode(),
            'returned_at' => $this->returned_at?->toIso8601String(),
            'employee' => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'division' => $this->employee->division,
                'phone' => $this->employee->phone,
            ],
            'car' => [
                'id' => $this->car->id,
                'plate_number' => $this->car->plate_number,
                'brand' => $this->car->brand,
                'model' => $this->car->model,
            ],
            // Aksi yang masuk akal untuk status saat ini (penanda antarmuka;
            // aturan sebenarnya dijaga di Action).
            'actions' => [
                'edit' => $this->status === BookingStatus::Scheduled,
                'cancel' => $this->status->canTransitionTo(BookingStatus::Cancelled),
                'pickup' => $this->status->canTransitionTo(BookingStatus::Active),
                'extend' => $this->status === BookingStatus::Active,
                'return' => $this->status->canTransitionTo(BookingStatus::Completed),
            ],
        ];
    }
}
