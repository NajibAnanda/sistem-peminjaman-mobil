<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Enums\BookingDisplayStatus;
use App\Models\Booking;
use App\Support\TableQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class BookingIndexRequest extends FormRequest
{
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

    public function tableQuery(): TableQuery
    {
        return TableQuery::fromRequest($this, [
            'status' => array_map(fn (BookingDisplayStatus $status): string => $status->value, BookingDisplayStatus::running()),
        ]);
    }
}
