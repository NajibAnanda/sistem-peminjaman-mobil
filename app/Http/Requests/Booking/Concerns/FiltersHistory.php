<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking\Concerns;

use App\Enums\BookingDisplayStatus;
use App\Enums\LatenessFilter;
use App\Models\Car;
use App\Models\Division;
use App\Models\Employee;
use App\Support\TableQuery;

/**
 * Parameter Riwayat yang sama untuk tabel dan ekspor, agar isi file selalu
 * mengikuti filter yang sedang tampil (FR-26). Nilai yang tidak dikenal
 * diabaikan (AGENTS.md 8.4).
 */
trait FiltersHistory
{
    /** @var list<array{value: string, label: string}>|null */
    private ?array $carOptions = null;

    /** @var list<array{value: string, label: string}>|null */
    private ?array $employeeOptions = null;

    public function tableQuery(): TableQuery
    {
        return TableQuery::fromRequest($this, [
            'status' => BookingDisplayStatus::values(),
            'car' => array_column($this->carOptions(), 'value'),
            'employee' => array_column($this->employeeOptions(), 'value'),
            'division' => $this->divisions(),
            'lateness' => LatenessFilter::values(),
        ], withPeriod: true, defaultToCurrentMonth: $this->periodDefaultsToCurrentMonth());
    }

    /**
     * Halaman Riwayat mengisi bulan berjalan bila periode belum dipilih.
     * Ekspor tidak: permintaan ekspor tanpa periode ditolak (lihat
     * ExportHistoryRequest), dan antarmuka selalu mengirim periode yang tampil.
     */
    protected function periodDefaultsToCurrentMonth(): bool
    {
        return false;
    }

    /**
     * Semua mobil, termasuk yang nonaktif, karena riwayatnya tetap ada.
     *
     * @return list<array{value: string, label: string}>
     */
    public function carOptions(): array
    {
        return $this->carOptions ??= array_values(Car::query()
            ->orderBy('brand')
            ->orderBy('model')
            ->orderBy('plate_number')
            ->get(['id', 'brand', 'model', 'plate_number'])
            ->map(fn (Car $car): array => [
                'value' => (string) $car->id,
                'label' => "{$car->brand} {$car->model} ({$car->plate_number})",
            ])
            ->all());
    }

    /**
     * Semua karyawan, termasuk yang nonaktif, karena riwayatnya tetap ada.
     *
     * @return list<array{value: string, label: string}>
     */
    public function employeeOptions(): array
    {
        return $this->employeeOptions ??= array_values(Employee::query()
            ->orderBy('name')
            ->get(['id', 'name', 'division'])
            ->map(fn (Employee $employee): array => [
                'value' => (string) $employee->id,
                'label' => "{$employee->name} ({$employee->division})",
            ])
            ->all());
    }

    /**
     * @return list<string>
     */
    public function divisions(): array
    {
        return array_values(array_unique(array_filter(array_merge(
            Division::query()->pluck('name')->all(),
            Employee::query()->distinct()->whereNotNull('division')->pluck('division')->all()
        ))));
    }
}
