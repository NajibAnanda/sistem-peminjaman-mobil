<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StaffIndexRequest extends FormRequest
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(StaffRole::class)],
            'status' => ['nullable', Rule::in([self::STATUS_ACTIVE, self::STATUS_INACTIVE])],
        ];
    }

    /**
     * @return array{search: string|null, role: string|null, status: string|null}
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'role' => $this->validated('role'),
            'status' => $this->validated('status'),
        ];
    }
}
