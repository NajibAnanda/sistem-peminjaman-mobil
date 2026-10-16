<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

/**
 * Halaman form jadwal (buat/edit). Rentang waktu opsional di query string
 * dipakai untuk menandai ketersediaan mobil; nilai yang tidak valid diabaikan.
 */
final class BookingFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking
            ? $this->user()?->can('update', $booking) ?? false
            : $this->user()?->can('create', Booking::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function range(): ?array
    {
        $start = $this->dateQuery('start_at');
        $end = $this->dateQuery('planned_return_at');

        return $start !== null && $end !== null && $end->greaterThan($start) ? [$start, $end] : null;
    }

    private function dateQuery(string $key): ?CarbonImmutable
    {
        $value = $this->query($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, (string) config('app.timezone'))->startOfMinute();
        } catch (Throwable) {
            return null;
        }
    }
}
