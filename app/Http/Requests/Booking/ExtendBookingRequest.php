<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use App\Support\BookingDuration;
use App\Support\DateFormat;
use App\Support\DateInput;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Throwable;

final class ExtendBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('extend', $this->booking()) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // AGENTS.md 5.14: wajib lebih lambat dari rencana kembali saat ini.
            'new_planned_return_at' => ['required', 'date', 'after:'.$this->booking()->planned_return_at->format('Y-m-d H:i:s')],
            'reason' => ['required', 'string', 'min:'.config('fleet.booking.reason_min_length'), 'max:500'],
        ];
    }

    /**
     * Durasi total (waktu mulai → rencana kembali baru) tetap dalam batas.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('new_planned_return_at')) {
                    return;
                }

                try {
                    $message = BookingDuration::violation($this->booking()->start_at, $this->newPlannedReturn());
                } catch (Throwable) {
                    return;
                }

                if ($message !== null) {
                    $validator->errors()->add('new_planned_return_at', $message);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_planned_return_at.after' => 'Rencana kembali baru harus lebih lambat dari rencana saat ini ('.DateFormat::dateTime($this->booking()->planned_return_at).').',
        ];
    }

    public function booking(): Booking
    {
        /** @var Booking */
        return $this->route('booking');
    }

    public function newPlannedReturn(): CarbonImmutable
    {
        return DateInput::parse($this->string('new_planned_return_at')->value());
    }

    public function reason(): string
    {
        return trim($this->string('reason')->value());
    }
}
