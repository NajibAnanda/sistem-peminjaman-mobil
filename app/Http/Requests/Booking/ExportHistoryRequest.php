<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Enums\ExportFormat;
use App\Http\Requests\Booking\Concerns\FiltersHistory;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Ekspor Riwayat (FR-26): filter sama dengan tabel, ditambah format file.
 * Periode wajib dipilih dan rentangnya dibatasi (config fleet.export) agar
 * file tidak terlalu besar untuk dibuka.
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

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $table = $this->tableQuery();
                $maxDays = (int) config('fleet.export.max_range_days');

                if ($table->from === null || $table->to === null) {
                    $validator->errors()->add('period', "Pilih periode terlebih dahulu (maksimal {$maxDays} hari) sebelum mengekspor.");

                    return;
                }

                // Kedua ujung inklusif: 1–1 Januari = 1 hari.
                $days = (int) $table->from->diffInDays($table->to->startOfDay()) + 1;

                if ($days > $maxDays) {
                    $validator->errors()->add('period', "Rentang periode ekspor maksimal {$maxDays} hari, sedangkan periode yang dipilih {$days} hari. Persempit periodenya.");
                }
            },
        ];
    }

    // Bukan format(): nama itu sudah dipakai Request untuk format respons.
    public function exportFormat(): ExportFormat
    {
        return ExportFormat::from($this->string('format')->value());
    }
}
