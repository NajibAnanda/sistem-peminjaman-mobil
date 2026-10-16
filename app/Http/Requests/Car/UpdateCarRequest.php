<?php

declare(strict_types=1);

namespace App\Http\Requests\Car;

use App\Http\Requests\Car\Concerns\CarAttributes;
use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCarRequest extends FormRequest
{
    use CarAttributes;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->car()) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->carRules($this->car()->id);
    }

    public function car(): Car
    {
        /** @var Car */
        return $this->route('car');
    }
}
