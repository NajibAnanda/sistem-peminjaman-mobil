<?php

declare(strict_types=1);

namespace App\Http\Requests\Car\Concerns;

use App\Models\Car;
use App\Support\PlateNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait CarAttributes
{
    /**
     * Pola plat nomor Indonesia setelah dinormalisasi: kode wilayah 1–2 huruf,
     * 1–4 angka, dan akhiran 0–3 huruf (mis. "B 1234 ABC", "RI 1").
     */
    private const PLATE_PATTERN = '/^[A-Z]{1,2} \d{1,4}( [A-Z]{1,3})?$/';

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('plate_number'))) {
            $this->merge(['plate_number' => PlateNumber::normalize($this->input('plate_number'))]);
        }

        foreach (['brand', 'model'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    /**
     * @return array<string, array<int, string|Unique>>
     */
    protected function carRules(?int $ignoreCarId = null): array
    {
        return [
            'plate_number' => [
                'required',
                'string',
                'max:15',
                'regex:'.self::PLATE_PATTERN,
                Rule::unique(Car::class)->ignore($ignoreCarId),
            ],
            'brand' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array{plate_number: string, brand: string, model: string}
     */
    public function carData(): array
    {
        return [
            'plate_number' => $this->string('plate_number')->value(),
            'brand' => $this->string('brand')->value(),
            'model' => $this->string('model')->value(),
        ];
    }
}
