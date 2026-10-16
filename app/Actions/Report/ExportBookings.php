<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\ExportFormat;
use App\Exports\BookingsExport;
use App\Models\Booking;
use App\Models\User;
use App\Support\TableQuery;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Ekspor Riwayat ke Excel/CSV mengikuti filter yang aktif (FR-26), lalu
 * mencatatnya di log aktivitas.
 */
final class ExportBookings
{
    private const FILE_PREFIX = 'riwayat-peminjaman';

    public function handle(TableQuery $table, ExportFormat $format, User $actor): BinaryFileResponse
    {
        $now = CarbonImmutable::now();
        $export = new BookingsExport($table, $now);
        $fileName = $this->fileName($table, $format, $now);

        activity(ActivityLogName::Report)
            ->causedBy($actor)
            ->event(ActivityEvent::Exported->value)
            ->withProperties([
                'format' => $format->value,
                'rows' => Booking::query()->forHistory($table, $now)->count(),
                'file' => $fileName,
                'query' => $table->toArray(),
            ])
            ->log("Mengekspor riwayat peminjaman ({$format->label()})");

        return Excel::download($export, $fileName, $format->writerType());
    }

    /**
     * Contoh: riwayat-peminjaman_2026-10-01_sd_2026-10-31_diekspor-2026-10-17-1430.xlsx
     */
    private function fileName(TableQuery $table, ExportFormat $format, CarbonImmutable $now): string
    {
        $period = match (true) {
            $table->from !== null && $table->to !== null => "{$table->from->toDateString()}_sd_{$table->to->toDateString()}",
            $table->from !== null => "sejak_{$table->from->toDateString()}",
            $table->to !== null => "sampai_{$table->to->toDateString()}",
            default => 'semua-periode',
        };

        return sprintf('%s_%s_diekspor-%s.%s', self::FILE_PREFIX, $period, $now->format('Y-m-d-Hi'), $format->value);
    }
}
