<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Enums\PhotoSource;
use App\Http\Requests\Concerns\ValidatesPhotoUpload;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Serah terima ambil & kembali: tepat 1 foto (FR-15, FR-16). Catatan kondisi
 * hanya dipakai saat kembali.
 */
final class HandoverRequest extends FormRequest
{
    use ValidatesPhotoUpload;

    public function authorize(): bool
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        return $this->user()?->can('handover', $booking) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => $this->photoRules(),
            'source' => ['required', Rule::enum(PhotoSource::class)],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function photoSource(): PhotoSource
    {
        return PhotoSource::from($this->string('source')->value());
    }

    public function returnNotes(): ?string
    {
        $notes = trim($this->string('return_notes')->value());

        return $notes === '' ? null : $notes;
    }
}
