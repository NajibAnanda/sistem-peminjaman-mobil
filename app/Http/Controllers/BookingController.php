<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Booking\ScheduleBooking;
use App\Actions\Booking\UpdateBookingSchedule;
use App\Enums\BookingDisplayStatus;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Controllers\Concerns\PaginatesForTable;
use App\Http\Requests\Booking\BookingFormRequest;
use App\Http\Requests\Booking\BookingIndexRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\BookingDetailResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Employee;
use App\Support\CarAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class BookingController extends Controller
{
    use PaginatesForTable;

    /**
     * Daftar peminjaman berjalan (Dijadwalkan & Aktif); yang sudah selesai
     * atau dibatalkan ada di Riwayat (Fase 6).
     */
    public function index(BookingIndexRequest $request): Response
    {
        $table = $request->tableQuery();

        $bookings = $this->paginateForTable(
            Booking::query()
                ->with(['employee', 'car'])
                ->withAwaitingReturn()
                ->ongoing()
                ->forTable($table)
                ->orderBy('start_at'),
        );

        return Inertia::render('bookings/index', [
            'bookings' => BookingResource::collection($bookings),
            'query' => $table->toArray(),
            'statuses' => array_map(
                fn (BookingDisplayStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                BookingDisplayStatus::running(),
            ),
            // Serah terima juga bisa dilakukan langsung dari daftar.
            'maxUploadKb' => (int) config('fleet.photos.max_upload_kb'),
        ]);
    }

    public function create(BookingFormRequest $request): Response
    {
        return $this->form($request, null);
    }

    public function store(StoreBookingRequest $request, ScheduleBooking $action): RedirectResponse
    {
        $booking = $action->handle($request->scheduleData(), $request->user());

        return to_route('bookings.show', $booking)->with('success', "Peminjaman {$booking->code} berhasil dijadwalkan.");
    }

    public function show(Booking $booking): Response
    {
        Gate::authorize('view', $booking);

        // Dimuat ulang dengan scope FR-23 (peringatan mobil belum kembali).
        $booking = Booking::query()->withAwaitingReturn()->findOrFail($booking->id);
        $booking->load([
            'employee', 'car', 'creator', 'pickedUpBy', 'returnedBy', 'cancelledBy',
            'photos.uploader', 'extensions.extender',
        ]);

        return Inertia::render('bookings/show', [
            'booking' => (new BookingDetailResource($booking))->resolve(),
            'maxUploadKb' => (int) config('fleet.photos.max_upload_kb'),
        ]);
    }

    public function edit(BookingFormRequest $request, Booking $booking): Response|RedirectResponse
    {
        // Tautan edit hanya tampil saat Dijadwalkan; URL yang dibuka langsung
        // diarahkan ke detail daripada menampilkan form yang pasti ditolak.
        if ($booking->status !== BookingStatus::Scheduled) {
            return to_route('bookings.show', $booking)
                ->with('error', BookingException::notEditable($booking->status)->getMessage());
        }

        return $this->form($request, $booking);
    }

    public function update(UpdateBookingRequest $request, Booking $booking, UpdateBookingSchedule $action): RedirectResponse
    {
        $action->handle($booking, $request->scheduleData(), $request->user());

        return to_route('bookings.show', $booking)->with('success', 'Jadwal peminjaman berhasil diperbarui.');
    }

    private function form(BookingFormRequest $request, ?Booking $booking): Response
    {
        // Saat edit, ketersediaan langsung ditandai untuk rentang booking itu
        // sendiri sampai petugas mengubah waktunya.
        [$start, $end] = $request->range()
            ?? ($booking === null ? [null, null] : [$booking->start_at, $booking->planned_return_at]);

        return Inertia::render('bookings/form', [
            'booking' => $booking === null ? null : (new BookingResource($booking->load(['employee', 'car'])))->resolve(),
            // Karyawan nonaktif disembunyikan sepenuhnya (prd.md 5.4).
            'employees' => Employee::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'employee_number', 'division'])
                ->map(fn (Employee $employee): array => $employee->only(['id', 'name', 'employee_number', 'division']))
                ->values()
                ->all(),
            // Dimuat ulang (partial reload) setiap rentang waktu berubah.
            'cars' => CarAvailability::forForm($start, $end, $booking),
            'startToleranceMinutes' => (int) config('fleet.booking.start_tolerance_minutes'),
        ]);
    }
}
