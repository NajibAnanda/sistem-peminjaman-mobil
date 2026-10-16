<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\ActivityPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\Activitylog\Models\Activity;

/**
 * Satu halaman Log Aktivitas. Dibuat sebagai koleksi (bukan per baris) agar
 * nama karyawan/mobil yang dirujuk seluruh baris dimuat sekaligus.
 * Membutuhkan relasi `causer` dan `subject`.
 */
final class ActivityLogCollection extends ResourceCollection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(Request $request): array
    {
        $activities = $this->collection
            ->map(fn (mixed $item): mixed => $item instanceof JsonResource ? $item->resource : $item)
            ->filter(fn (mixed $item): bool => $item instanceof Activity)
            ->values();

        $presenter = ActivityPresenter::for($activities);

        return array_values($activities->map(fn (Activity $activity): array => $presenter->present($activity))->all());
    }
}
