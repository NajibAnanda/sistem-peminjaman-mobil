<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BookingDisplayStatus;
use App\Enums\ExportFormat;
use App\Enums\LatenessFilter;
use App\Http\Controllers\Concerns\PaginatesForTable;
use App\Http\Requests\Booking\HistoryIndexRequest;
use App\Http\Resources\HistoryResource;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Riwayat seluruh peminjaman dengan pencarian, filter, dan periode (FR-25).
 */
final class HistoryController extends Controller
{
    use PaginatesForTable;

    public function __invoke(HistoryIndexRequest $request): Response
    {
        $table = $request->tableQuery();

        $bookings = $this->paginateForTable(
            Booking::query()
                ->with(['employee', 'car'])
                ->forHistory($table)
                ->orderByDesc('start_at')
                ->orderByDesc('id'),
        );

        // Dihitung untuk 10 baris halaman ini saja; withCount di query utama
        // ikut dievaluasi untuk baris yang dilewati paginasi.
        (new EloquentCollection($bookings->items()))->loadCount('extensions');

        return Inertia::render('history/index', [
            'bookings' => HistoryResource::collection($bookings),
            'query' => $table->toArray(),
            'filterOptions' => [
                'status' => BookingDisplayStatus::options(),
                'lateness' => LatenessFilter::options(),
                'division' => array_map(fn (string $division): array => ['value' => $division, 'label' => $division], $request->divisions()),
                'car' => $request->carOptions(),
                'employee' => $request->employeeOptions(),
            ],
            'exportFormats' => ExportFormat::options(),
        ]);
    }
}
