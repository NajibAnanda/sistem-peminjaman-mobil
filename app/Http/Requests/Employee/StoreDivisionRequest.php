<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Models\Division;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Division::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique(Division::class, 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama divisi wajib diisi.',
            'name.max' => 'Nama divisi tidak boleh lebih dari 50 karakter.',
            'name.unique' => 'Divisi ini sudah ada.',
        ];
    }

    public function divisionName(): string
    {
        return trim((string) $this->validated('name'));
    }
}
