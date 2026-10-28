<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Models\Division;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class DestroyDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', Division::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
