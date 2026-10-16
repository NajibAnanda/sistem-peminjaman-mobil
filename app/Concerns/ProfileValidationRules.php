<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * @return array<int, string|Unique>
     */
    protected function emailRules(?int $ignoreUserId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $ignoreUserId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($ignoreUserId),
        ];
    }

    /**
     * Login Fortify mengecilkan email, jadi email harus disimpan huruf kecil
     * agar akun tetap bisa masuk.
     */
    protected function normalizeEmail(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }
}
