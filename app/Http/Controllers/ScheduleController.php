<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Booking\ScheduleIndexRequest;
use App\Models\Booking;
use App\Models\Car;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jadwal Armada: timeline mingguan per mobil (FR-24).
 */
final class ScheduleController extends Controller
{
    private const DAYS_PER_WEEK = 7;

    public function __invoke(ScheduleIndexRequest $request): Response
    {
        $now = CarbonImmutable::now();
        $weekStart = $request->weekStart();
        $weekEnd = $weekStart->addDays(self::DAYS_PER_WEEK);

        $bookings = Booking::query()
            ->with('employee:id,name')
            ->visibleOnTimeline($weekStart, $weekEnd)
            ->orderBy('start_at')
            ->get()
            ->filter(function (Booking $booking) use ($now, $weekStart, $weekEnd): bool {
                [$start, $end] = $booking->timelineRange($now);

                return $start->lessThan($weekEnd) && $end->greaterThan($weekStart);
            });

        // Mobil aktif selalu tampil; mobil nonaktif hanya bila punya
        // peminjaman di minggu ini (data historis).
        $cars = Car::query()
            ->withDisplayStatus()
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->orWhereIn('id', $bookings->pluck('car_id')->unique()->all()))
            ->orderBy('plate_number')
            ->get();

        return Inertia::render('schedule/index', [
            'now' => $now->toIso8601String(),
            'week' => [
                'start' => $weekStart->toIso8601String(),
                'end' => $weekEnd->toIso8601String(),
                'previous' => $weekStart->subWeek()->toDateString(),
                'next' => $weekStart->addWeek()->toDateString(),
                'is_current' => $now->greaterThanOrEqualTo($weekStart) && $now->lessThan($weekEnd),
                'days' => array_map(fn (int $offset): string => $weekStart->addDays($offset)->toDateString(), range(0, self::DAYS_PER_WEEK - 1)),
            ],
            'cars' => $cars->map(fn (Car $car): array => [
                'id' => $car->id,
                'plate_number' => $car->plate_number,
                'brand' => $car->brand,
                'model' => $car->model,
                'display_status' => $car->displayStatus()->value,
            ])->values()->all(),
            'bookings' => $bookings->map(function (Booking $booking) use ($now): array {
                [$start, $end] = $booking->timelineRange($now);
                $status = $booking->displayStatus($now);

                return [
                    'id' => $booking->id,
                    'code' => $booking->code,
                    'car_id' => $booking->car_id,
                    'employee_name' => $booking->employee->name,
                    'purpose' => $booking->purpose,
                    'display_status' => $status->value,
                    'display_status_label' => $status->label(),
                    'start_at' => $start->toIso8601String(),
                    'end_at' => $end->toIso8601String(),
                ];
            })->values()->all(),
        ]);
    }
}
