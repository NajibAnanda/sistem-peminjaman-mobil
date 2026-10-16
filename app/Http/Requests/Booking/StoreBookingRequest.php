<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\Booking\Concerns\BookingScheduleAttributes;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreBookingRequest extends FormRequest
{
    use BookingScheduleAttributes;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Booking::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->scheduleRules(checkStartTolerance: true);
    }
}
