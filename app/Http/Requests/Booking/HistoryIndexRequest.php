<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\Booking\Concerns\FiltersHistory;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class HistoryIndexRequest extends FormRequest
{
    use FiltersHistory;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Booking::class) ?? false;
    }

    /**
     * Parameter tabel tidak divalidasi dengan pesan error: nilai yang tidak
     * dikenal diabaikan oleh TableQuery (AGENTS.md 8.4).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
