<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\PhotoStage;
use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\BookingPhoto;
use Illuminate\Http\Request;

/**
 * Detail peminjaman. Membutuhkan relasi employee, car, creator, pickedUpBy,
 * returnedBy, cancelledBy, photos.uploader, dan extensions.extender.
 *
 * @mixin Booking
 */
final class BookingDetailResource extends BookingResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photos = $this->photos->keyBy(fn (BookingPhoto $photo): string => $photo->stage->value);

        return [
            ...parent::toArray($request),
            'return_notes' => $this->return_notes,
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'created_by_name' => $this->creator->name,
            'picked_up_by_name' => $this->pickedUpBy?->name,
            'returned_by_name' => $this->returnedBy?->name,
            'cancelled_by_name' => $this->cancelledBy?->name,
            'photos' => [
                PhotoStage::Pickup->value => $this->photo($photos->get(PhotoStage::Pickup->value)),
                PhotoStage::Return->value => $this->photo($photos->get(PhotoStage::Return->value)),
            ],
            'extensions' => $this->extensions->map(fn (BookingExtension $extension): array => [
                'id' => $extension->id,
                'previous_planned_return_at' => $extension->previous_planned_return_at->toIso8601String(),
                'new_planned_return_at' => $extension->new_planned_return_at->toIso8601String(),
                'reason' => $extension->reason,
                'extended_by_name' => $extension->extender->name,
                'created_at' => $extension->created_at->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * Foto bukti hanya lewat route ber-Policy; path storage tidak pernah dikirim.
     *
     * @return array{url: string, taken_at: string, source_label: string, uploaded_by_name: string}|null
     */
    private function photo(?BookingPhoto $photo): ?array
    {
        if ($photo === null) {
            return null;
        }

        return [
            'url' => route('bookings.photos.show', ['booking' => $this->id, 'stage' => $photo->stage->value]),
            'taken_at' => $photo->taken_at->toIso8601String(),
            'source_label' => $photo->source->label(),
            'uploaded_by_name' => $photo->uploader->name,
        ];
    }
}
