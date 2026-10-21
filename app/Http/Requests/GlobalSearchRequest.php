<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pencarian global. Hak akses per kategori dicek di GlobalSearch lewat Policy;
 * di sini cukup memastikan petugas sudah login.
 */
final class GlobalSearchRequest extends FormRequest
{
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 100;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:'.self::MIN_LENGTH, 'max:'.self::MAX_LENGTH],
        ];
    }

    public function term(): string
    {
        return trim($this->string('q')->value());
    }
}
