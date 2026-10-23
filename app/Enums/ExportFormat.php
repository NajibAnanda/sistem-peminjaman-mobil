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
    case Pdf = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::Xlsx => 'Microsoft Excel (.xlsx)',
            self::Csv => 'CSV (.csv)',
            self::Pdf => 'Dokumen PDF (.pdf)',
        };
    }

    public function writerType(): string
    {
        return match ($this) {
            self::Xlsx => Excel::XLSX,
            self::Csv => Excel::CSV,
            self::Pdf => Excel::DOMPDF,
        };
    }
}
