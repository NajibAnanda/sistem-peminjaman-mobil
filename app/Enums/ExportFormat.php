<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Maatwebsite\Excel\Excel;

/**
 * Format file laporan Riwayat (FR-26).
 */
enum ExportFormat: string
{
    use HasOptions;

    case Xlsx = 'xlsx';
    case Csv = 'csv';

    public function label(): string
    {
        return match ($this) {
            self::Xlsx => 'Microsoft Excel (.xlsx)',
            self::Csv => 'CSV (.csv)',
        };
    }

    public function writerType(): string
    {
        return match ($this) {
            self::Xlsx => Excel::XLSX,
            self::Csv => Excel::CSV,
        };
    }
}
