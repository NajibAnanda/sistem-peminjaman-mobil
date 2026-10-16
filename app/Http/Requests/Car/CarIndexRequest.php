<?php

declare(strict_types=1);

namespace App\Http\Requests\Car;

use App\Enums\CarDisplayStatus;
use App\Models\Car;
use App\Support\TableQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CarIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Car::class) ?? false;
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
            'status' => CarDisplayStatus::values(),
        ]);
    }
}
