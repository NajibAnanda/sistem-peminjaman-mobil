<?php

declare(strict_types=1);

namespace App\Http\Requests\Car;

use App\Enums\CarOperationalStatus;
use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCarOperationalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Car $car */
        $car = $this->route('car');

        return $this->user()?->can('changeOperationalStatus', $car) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'operational_status' => ['required', Rule::enum(CarOperationalStatus::class)],
        ];
    }

    public function operationalStatus(): CarOperationalStatus
    {
        return CarOperationalStatus::from($this->string('operational_status')->value());
    }
}
