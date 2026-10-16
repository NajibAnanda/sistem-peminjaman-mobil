<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Jadwal Armada (FR-24). Parameter `week` berisi tanggal mana pun di minggu
 * yang ingin dilihat; nilai yang tidak valid diabaikan (kembali ke minggu ini).
 */
final class ScheduleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Booking::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Senin pukul 00.00 dari minggu yang diminta (minggu di Indonesia dimulai Senin).
     */
    public function weekStart(): CarbonImmutable
    {
        $timezone = (string) config('app.timezone');
        $value = $this->query('week');
        $date = CarbonImmutable::now($timezone);

        // Tanggal yang tidak ada (mis. 2026-02-31) diabaikan, bukan digeser.
        if (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) === 1
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            $date = CarbonImmutable::create((int) $parts[1], (int) $parts[2], (int) $parts[3], 0, 0, 0, $timezone);
        }

        return $date->startOfWeek(CarbonInterface::MONDAY);
    }
}
