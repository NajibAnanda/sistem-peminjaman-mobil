<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\Booking\Concerns\BookingScheduleAttributes;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

final class UpdateBookingRequest extends FormRequest
{
    use BookingScheduleAttributes;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->booking()) ?? false;
    }

    /**
     * Toleransi waktu mulai hanya divalidasi bila waktu mulai diubah, agar
     * peminjaman Belum Diambil tetap bisa diedit (AGENTS.md 5.11).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->scheduleRules(checkStartTolerance: $this->startChanged());
    }

    public function booking(): Booking
    {
        /** @var Booking */
        return $this->route('booking');
    }

    private function startChanged(): bool
    {
        try {
            $submitted = CarbonImmutable::parse((string) $this->input('start_at'), (string) config('app.timezone'))->startOfMinute();
        } catch (Throwable) {
            // Bukan tanggal: biarkan aturan 'date' yang menolak.
            return true;
        }

        return ! $submitted->equalTo($this->booking()->start_at->copy()->startOfMinute());
    }
}
