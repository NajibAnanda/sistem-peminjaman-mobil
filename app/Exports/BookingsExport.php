<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Booking;
use App\Support\DateFormat;
use App\Support\TableQuery;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Laporan Riwayat (prd.md 3.8). Query sama dengan tabel Riwayat (scope
 * forHistory), dibaca bertahap (chunk) agar hemat memori untuk data besar.
 * WithStrictNullComparison wajib: tanpanya angka 0 (mis. tanpa perpanjangan)
 * dianggap null dan selnya dikosongkan.
 *
 * @implements WithMapping<Booking>
 */
final class BookingsExport implements FromQuery, WithColumnWidths, WithCustomChunkSize, WithCustomCsvSettings, WithCustomValueBinder, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles
{
    public function __construct(
        private readonly TableQuery $table,
        private readonly CarbonInterface $now,
    ) {}

    /**
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return Booking::query()
            ->with(['employee', 'car', 'creator'])
            ->withCount('extensions')
            ->forHistory($this->table, $this->now)
            // Sama dengan tabel Riwayat (terbaru di atas). Urutan harus stabil
            // (id sebagai penentu) agar chunk tidak melewatkan/menggandakan baris.
            ->orderByDesc('start_at')
            ->orderByDesc('id');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Kode Peminjaman',
            'Nama Karyawan',
            'Divisi',
            'No HP',
            'Mobil',
            'Plat Nomor',
            'Tujuan',
            'Waktu Mulai',
            'Rencana Kembali',
            'Waktu Ambil',
            'Waktu Kembali',
            'Status',
            'Jumlah Perpanjangan',
            'Durasi Keterlambatan',
            'Petugas Pencatat',
        ];
    }

    /**
     * @param  Booking  $row
     * @return list<string|int>
     */
    public function map(mixed $row): array
    {
        $lateness = $row->latenessMinutes($this->now);

        return [
            $row->code,
            $row->employee->name,
            $row->employee->division,
            $row->employee->phone,
            "{$row->car->brand} {$row->car->model}",
            $row->car->plate_number,
            $row->purpose,
            DateFormat::dateTime($row->start_at),
            DateFormat::dateTime($row->planned_return_at),
            $row->picked_up_at === null ? '' : DateFormat::dateTime($row->picked_up_at),
            $row->returned_at === null ? '' : DateFormat::dateTime($row->returned_at),
            $row->displayStatus($this->now)->label(),
            (int) $row->extensions_count,
            $lateness === null ? '' : DateFormat::duration($lateness),
            $row->creator->name,
        ];
    }

    /**
     * Lebar kolom tetap (dalam karakter). Ukuran otomatis (ShouldAutoSize)
     * mengukur setiap sel dan sangat memperlambat ekspor data besar.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 28, 'C' => 16, 'D' => 16, 'E' => 22, 'F' => 13, 'G' => 40, 'H' => 19,
            'I' => 19, 'J' => 19, 'K' => 19, 'L' => 14, 'M' => 12, 'N' => 18, 'O' => 22,
        ];
    }

    public function chunkSize(): int
    {
        return (int) config('fleet.export.chunk_size');
    }

    /**
     * BOM UTF-8 agar Excel membaca huruf non-ASCII dengan benar.
     *
     * @return array<string, mixed>
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => (string) config('fleet.export.csv_delimiter'),
            'use_bom' => true,
        ];
    }

    /**
     * Teks disimpan sebagai teks apa adanya: no HP yang diawali 08 tidak boleh
     * diubah Excel menjadi angka (nol di depan hilang).
     * Hanya kolom jumlah yang disimpan sebagai angka.
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_int($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
        } else {
            $cell->setValueExplicit(is_scalar($value) ? (string) $value : '', DataType::TYPE_STRING);
        }

        return true;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = 'O';

        // Header baris 1: navy gelap kontras, teks putih tebal, tinggi 28pt
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E293B'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->setAutoFilter("A1:{$highestColumn}1");

        if ($highestRow >= 2) {
            // Garis kisi tipis untuk seluruh tabel
            $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Zebra striping khusus baris isi tabel: baris ganjil data (row 3, 5, ...) diberi aksen abu-abu sangat lembut
            for ($row = 2; $row <= $highestRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(20);
                if ($row % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFF8FAFC'],
                        ],
                    ]);
                }
            }
        }

        return [];
    }
}
