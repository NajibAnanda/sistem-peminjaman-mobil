<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActivityLogName;
use App\Http\Controllers\Concerns\PaginatesForTable;
use App\Http\Requests\ActivityLogIndexRequest;
use App\Http\Resources\ActivityLogCollection;
use App\Models\User;
use App\Support\SearchTerm;
use App\Support\TableQuery;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Log Aktivitas khusus Pengelola (FR-27). Hanya baca: tidak ada aksi ubah
 * atau hapus log.
 */
final class ActivityLogController extends Controller
{
    use PaginatesForTable;

    public function __invoke(ActivityLogIndexRequest $request): Response
    {
        $table = $request->tableQuery();

        return Inertia::render('activity-log/index', [
            'activities' => new ActivityLogCollection($this->paginateForTable($this->query($table))),
            'query' => $table->toArray(),
            'filterOptions' => [
                'type' => ActivityLogName::options(),
                'staff' => $request->staffOptions(),
            ],
        ]);
    }

    /**
     * @return Builder<Activity>
     */
    private function query(TableQuery $table): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->when($table->search, function (Builder $query, string $term): void {
                $escaped = SearchTerm::escape($term);

                $query->where('description', 'like', "%{$escaped}%");
            })
            ->when($table->filter('type'), fn (Builder $query, array $types) => $query->whereIn('log_name', $types))
            ->when($table->filter('staff'), fn (Builder $query, array $ids) => $query
                ->where('causer_type', (new User)->getMorphClass())
                ->whereIn('causer_id', $ids))
            ->when($table->from, fn (Builder $query, CarbonInterface $from) => $query->where('created_at', '>=', $from))
            ->when($table->to, fn (Builder $query, CarbonInterface $to) => $query->where('created_at', '<=', $to))
            ->latest('id');
    }
}
