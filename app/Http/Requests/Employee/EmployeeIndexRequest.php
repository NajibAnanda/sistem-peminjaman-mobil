<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Enums\ActiveStatus;
use App\Models\Employee;
use App\Support\TableQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class EmployeeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Employee::class) ?? false;
    }

    /**
     * Parameter tabel tidak divalidasi dengan pesan error: nilai yang tidak
     * dikenal diabaikan oleh TableQuery (AGENTS.md 8.4).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    public function tableQuery(): TableQuery
    {
        /** @var list<string> $divisions */
        $divisions = config('fleet.divisions');

        return TableQuery::fromRequest($this, [
            'division' => $divisions,
            'status' => ActiveStatus::values(),
        ]);
    }
}
