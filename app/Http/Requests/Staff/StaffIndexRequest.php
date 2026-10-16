<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Enums\ActiveStatus;
use App\Enums\StaffRole;
use App\Models\User;
use App\Support\TableQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StaffIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * Parameter tabel tidak divalidasi dengan pesan error: nilai yang tidak
     * dikenal diabaikan oleh TableQuery (whitelist di bawah).
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
            'role' => StaffRole::values(),
            'status' => ActiveStatus::values(),
        ]);
    }
}
