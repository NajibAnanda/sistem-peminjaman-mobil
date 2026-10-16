<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Http\Requests\Employee\Concerns\EmployeeAttributes;
use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEmployeeRequest extends FormRequest
{
    use EmployeeAttributes;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->employeeRules();
    }
}
