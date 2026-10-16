<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Concerns\ProfileValidationRules;
use App\Enums\StaffRole;
use App\Http\Requests\Staff\Concerns\ProvidesStaffData;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStaffRequest extends FormRequest
{
    use ProfileValidationRules;
    use ProvidesStaffData;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeEmail();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules(),
            'role' => ['required', Rule::enum(StaffRole::class)],
        ];
    }
}
