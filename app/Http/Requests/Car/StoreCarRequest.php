<?php

declare(strict_types=1);

namespace App\Http\Requests\Car;

use App\Http\Requests\Car\Concerns\CarAttributes;
use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCarRequest extends FormRequest
{
    use CarAttributes;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Car::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->carRules();
    }
}
