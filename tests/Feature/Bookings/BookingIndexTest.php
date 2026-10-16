<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return list<string>
 */
function bookingCodes(Assert $page): array
{
    return collect($page->toArray()['props']['bookings']['data'])->pluck('code')->sort()->values()->all();
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00'));
    $this->user = User::factory()->admin()->create();

    $make = fn (string $code, BookingStatus $status, string $start, string $end, array $extra = []) => Booking::factory()->create([
        'code' => $code,
        'status' => $status,
        'start_at' => CarbonImmutable::parse($start),
        'planned_return_at' => CarbonImmutable::parse($end),
        'picked_up_at' => $status === BookingStatus::Active ? CarbonImmutable::parse($start) : null,
        ...$extra,
    ]);

    $make('PMJ-202609-0001', BookingStatus::Scheduled, '2026-10-21 08:00', '2026-10-21 12:00', [
        'employee_id' => Employee::factory()->create(['name' => 'Rina Kartika', 'employee_number' => 'KRY777'])->id,
    ]);
    $make('PMJ-202609-0002', BookingStatus::Scheduled, '2026-10-20 09:00', '2026-10-20 15:00');
    $make('PMJ-202609-0003', BookingStatus::Active, '2026-10-20 08:00', '2026-10-20 18:00', [
        'car_id' => Car::factory()->create(['plate_number' => 'D 4321 XYZ'])->id,
    ]);
    $make('PMJ-202609-0004', BookingStatus::Active, '2026-10-20 06:00', '2026-10-20 10:00');
    $make('PMJ-202609-0005', BookingStatus::Completed, '2026-10-19 08:00', '2026-10-19 12:00', ['returned_at' => CarbonImmutable::parse('2026-10-19 11:00')]);
    $make('PMJ-202609-0006', BookingStatus::Cancelled, '2026-10-22 08:00', '2026-10-22 12:00');
});

test('daftar hanya berisi peminjaman berjalan dengan status turunan', function () {
    $this->actingAs($this->user)
        ->get(route('bookings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookings/index')
            ->has('bookings.data', 4)
            ->where('bookings.data', fn ($rows) => collect($rows)->mapWithKeys(fn ($row) => [
                $row['code'] => $row['display_status'],
            ])->sortKeys()->all() === [
                'PMJ-202609-0001' => 'scheduled',
                'PMJ-202609-0002' => 'not_picked_up',
                'PMJ-202609-0003' => 'active',
                'PMJ-202609-0004' => 'overdue',
            ])
            ->where('statuses', [
                ['value' => 'scheduled', 'label' => 'Dijadwalkan'],
                ['value' => 'active', 'label' => 'Aktif'],
                ['value' => 'overdue', 'label' => 'Terlambat'],
                ['value' => 'not_picked_up', 'label' => 'Belum Diambil'],
            ])
            ->where('maxUploadKb', 5120)
            ->missing('bookings.data.0.employee.created_at'),
        );
});

test('pencarian dan filter status mengembalikan data yang benar', function (array $query, array $expected) {
    $this->actingAs($this->user)
        ->get(route('bookings.index', $query))
        ->assertInertia(fn (Assert $page) => expect(bookingCodes($page))->toBe($expected));
})->with([
    'cari kode' => [['search' => '0002'], ['PMJ-202609-0002']],
    'cari nama karyawan' => [['search' => 'rina'], ['PMJ-202609-0001']],
    'cari NIP' => [['search' => 'KRY777'], ['PMJ-202609-0001']],
    'cari plat' => [['search' => '4321'], ['PMJ-202609-0003']],
    'kode selesai tidak muncul' => [['search' => '0005'], []],
    'dijadwalkan' => [['status' => ['scheduled']], ['PMJ-202609-0001']],
    'aktif' => [['status' => ['active']], ['PMJ-202609-0003']],
    'terlambat' => [['status' => ['overdue']], ['PMJ-202609-0004']],
    'belum diambil' => [['status' => ['not_picked_up']], ['PMJ-202609-0002']],
    'gabungan' => [['status' => ['overdue', 'not_picked_up']], ['PMJ-202609-0002', 'PMJ-202609-0004']],
]);

test('parameter filter tidak dikenal diabaikan', function () {
    $this->actingAs($this->user)
        ->get(route('bookings.index', ['status' => ['completed', 'hapus'], 'urut' => 'x']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('bookings.data', 4)
            ->where('query.filters.status', []),
        );
});

test('nomor urut halaman 2 dimulai dari 11', function () {
    Booking::factory()->count(10)->scheduled()->create();

    $this->actingAs($this->user)
        ->get(route('bookings.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('bookings.meta.from', 11)
            ->has('bookings.data', 4),
        );
});
