<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\CarDisplayStatus;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Car;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ringkasan harian petugas GA (FR-20 s.d. FR-23). Semua daftar dihitung
 * saat ditampilkan; status Terlambat & Belum Diambil tidak disimpan.
 */
final class DashboardController extends Controller
{
    /**
     * Daftar Terlambat & Belum Diambil dibatasi; jumlah lengkapnya ada di
     * badge dan selebihnya dibuka lewat filter di halaman Peminjaman. Jadwal
     * hari ini tidak dibatasi agar tidak ada serah terima yang terlewat.
     */
    private const ALERT_LIST_LIMIT = 10;

    public function __invoke(): Response
    {
        Gate::authorize('viewAny', Booking::class);

        $now = CarbonImmutable::now();
        $today = [$now->startOfDay(), $now->endOfDay()];

        // Ringkasan armada, Terlambat, dan Belum Diambil dimuat setelah halaman
        // tampil (deferred) agar dashboard langsung terbuka; sementara itu
        // frontend menampilkan skeleton. Jadwal hari ini tetap dimuat langsung.
        return Inertia::render('dashboard', [
            'now' => $now->toIso8601String(),
            'alertListLimit' => self::ALERT_LIST_LIMIT,
            'fleet' => Inertia::defer(fn (): array => $this->fleetSummary()),
            'pickupsToday' => $this->list(fn (Builder $query) => $query
                ->where('status', BookingStatus::Scheduled)
                ->whereBetween('start_at', $today)
                ->orderBy('start_at'), $now),
            'returnsToday' => $this->list(fn (Builder $query) => $query
                ->where('status', BookingStatus::Active)
                ->whereBetween('planned_return_at', $today)
                ->orderBy('planned_return_at'), $now),
            'overdue' => Inertia::defer(fn (): array => $this->list(fn (Builder $query) => $query
                ->overdue($now)
                ->orderBy('planned_return_at'), $now, self::ALERT_LIST_LIMIT)),
            'notPickedUp' => Inertia::defer(fn (): array => $this->list(fn (Builder $query) => $query
                ->notPickedUp($now)
                ->orderBy('start_at'), $now, self::ALERT_LIST_LIMIT)),
        ]);
    }

    /**
     * Jumlah mobil per status tampilan (Tersedia, Dipinjam, Maintenance,
     * Nonaktif). Armada kantor kecil, jadi dihitung dari satu query.
     *
     * @return list<array{status: string, label: string, count: int}>
     */
    private function fleetSummary(): array
    {
        $counts = Car::query()
            ->withDisplayStatus()
            ->get()
            ->countBy(fn (Car $car): string => $car->displayStatus()->value);

        return array_map(fn (CarDisplayStatus $status): array => [
            'status' => $status->value,
            'label' => $status->label(),
            'count' => (int) $counts->get($status->value, 0),
        ], CarDisplayStatus::cases());
    }

    /**
     * @param  callable(Builder<Booking>): mixed  $constrain
     * @return list<array<string, mixed>>
     */
    private function list(callable $constrain, CarbonImmutable $now, ?int $limit = null): array
    {
        $query = Booking::query()->with(['employee', 'car'])->withAwaitingReturn($now);
        $constrain($query);

        return array_values(BookingResource::collection($query->when($limit, fn (Builder $query, int $limit) => $query->limit($limit))->get())->resolve());
    }
}
