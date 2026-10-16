<?php

declare(strict_types=1);

namespace App\Http\Requests\Car;

use App\Http\Requests\Concerns\ValidatesPhotoUpload;
use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCarPhotoRequest extends FormRequest
{
    use ValidatesPhotoUpload;

    public function authorize(): bool
    {
        /** @var Car $car */
        $car = $this->route('car');

        return $this->user()?->can('updatePhoto', $car) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => $this->photoRules(),
        ];
    }

    public function position(): int
    {
        return (int) $this->route('position');
    }
}
