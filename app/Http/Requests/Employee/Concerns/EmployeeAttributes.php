<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee\Concerns;

use App\Models\Employee;
use App\Support\PhoneNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;

trait EmployeeAttributes
{
    /**
     * Normalisasi sebelum validasi (AGENTS.md 5.17): no HP ke format 08xx agar
     * panjang dihitung setelah normalisasi.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => PhoneNumber::normalize($this->input('phone')) ?? trim($this->input('phone'))]);
        }

        if (is_string($this->input('division'))) {
            $this->merge(['division' => trim($this->input('division'))]);
        }
    }

    /**
     * @return array<string, array<int, string|Unique|In>>
     */
    protected function employeeRules(?int $ignoreEmployeeId = null): array
    {
        $min = PhoneNumber::MIN_DIGITS - 2;
        $max = PhoneNumber::MAX_DIGITS - 2;

        return [
            'name' => ['required', 'string', 'max:255'],
            'division' => ['required', 'string', 'max:50'],
            'phone' => [
                'required',
                'string',
                "regex:/^08\\d{{$min},{$max}}$/",
                Rule::unique(Employee::class, 'phone')->ignore($ignoreEmployeeId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.',
            'phone.unique' => 'No HP sudah dipakai karyawan lain.',
        ];
    }

    /**
     * @return array{name: string, division: string, phone: string}
     */
    public function employeeData(): array
    {
        return [
            'name' => $this->string('name')->value(),
            'division' => $this->string('division')->value(),
            'phone' => $this->string('phone')->value(),
        ];
    }
}
