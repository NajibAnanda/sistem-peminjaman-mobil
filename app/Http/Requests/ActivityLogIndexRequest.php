<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ActivityLogName;
use App\Models\User;
use App\Support\TableQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Log Aktivitas (FR-27): khusus Pengelola. Filter jenis data, petugas, dan
 * periode; nilai yang tidak dikenal diabaikan (AGENTS.md 8.4).
 */
final class ActivityLogIndexRequest extends FormRequest
{
    /** @var list<array{value: string, label: string}>|null */
    private ?array $staffOptions = null;

    public function authorize(): bool
    {
        return $this->user()?->can('view-activity-log') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    public function tableQuery(): TableQuery
    {
        return TableQuery::fromRequest($this, [
            'type' => ActivityLogName::values(),
            'staff' => array_column($this->staffOptions(), 'value'),
        ], withPeriod: true);
    }

    /**
     * Semua akun petugas, termasuk yang nonaktif, karena lognya tetap ada.
     *
     * @return list<array{value: string, label: string}>
     */
    public function staffOptions(): array
    {
        return $this->staffOptions ??= array_values(User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name])
            ->all());
    }
}
