<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Enums\ExportFormat;
use App\Http\Requests\Booking\Concerns\FiltersHistory;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ekspor Riwayat (FR-26): filter sama dengan tabel, ditambah format file.
 */
final class ExportHistoryRequest extends FormRequest
{
    use FiltersHistory;

    public function authorize(): bool
    {
        return $this->user()?->can('export', Booking::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', Rule::enum(ExportFormat::class)],
        ];
    }

    // Bukan format(): nama itu sudah dipakai Request untuk format respons.
    public function exportFormat(): ExportFormat
    {
        return ExportFormat::from($this->string('format')->value());
    }
}
