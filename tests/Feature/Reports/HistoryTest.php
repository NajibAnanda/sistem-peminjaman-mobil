<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * @return list<string>
 */
// Periode yang mencakup seluruh data uji (September–Oktober 2026).
const HISTORY_ALL = ['from' => '2026-09-01', 'to' => '2026-10-31'];

function historyCodes(Assert $page): array
{
    return collect($page->toArray()['props']['bookings']['data'])->pluck('code')->sort()->values()->all();
}

beforeEach(function () {
    /** @var TestCase $this */
    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00'));
    $this->user = User::factory()->admin()->create();

    $this->carA = Car::factory()->create(['brand' => 'Daihatsu', 'model' => 'Terios', 'plate_number' => 'B 1111 AAA']);
    $this->carB = Car::factory()->create(['brand' => 'Toyota', 'model' => 'Avanza', 'plate_number' => 'D 2222 BBB']);
    $this->rina = Employee::factory()->create(['name' => 'Rina Kartika', 'division' => 'Keuangan']);
    $this->budi = Employee::factory()->create(['name' => 'Budi Santoso', 'division' => 'Teknik']);

    $make = fn (string $code, BookingStatus $status, Car $car, Employee $employee, string $start, string $end, array $extra = []) => Booking::factory()->create([
        'code' => $code,
        'status' => $status,
        'car_id' => $car->id,
        'employee_id' => $employee->id,
        'start_at' => CarbonImmutable::parse($start),
        'planned_return_at' => CarbonImmutable::parse($end),
        'picked_up_at' => in_array($status, [BookingStatus::Active, BookingStatus::Completed], true) ? CarbonImmutable::parse($start) : null,
        ...$extra,
    ]);

    // Selesai terlambat 2 jam 30 menit, diperpanjang sekali.
    $this->late = $make('PMJ-202609-9001', BookingStatus::Completed, $this->carA, $this->rina, '2026-09-10 08:00', '2026-09-10 12:00', [
        'returned_at' => CarbonImmutable::parse('2026-09-10 14:30'),
    ]);
    BookingExtension::factory()->for($this->late)->create();
    // Selesai tepat waktu.
    $make('PMJ-202609-9002', BookingStatus::Completed, $this->carB, $this->budi, '2026-09-25 08:00', '2026-09-25 17:00', [
        'returned_at' => CarbonImmutable::parse('2026-09-25 16:00'),
    ]);
    $make('PMJ-202610-9001', BookingStatus::Cancelled, $this->carA, $this->budi, '2026-10-05 08:00', '2026-10-05 12:00', [
        'cancelled_at' => CarbonImmutable::parse('2026-10-04 08:00'),
        'cancellation_reason' => 'Batal',
    ]);
    // Aktif dan sudah Terlambat.
    $make('PMJ-202610-9002', BookingStatus::Active, $this->carB, $this->rina, '2026-10-19 08:00', '2026-10-20 09:00');
    $make('PMJ-202610-9003', BookingStatus::Scheduled, $this->carA, $this->rina, '2026-10-22 08:00', '2026-10-22 12:00');
});

test('riwayat berisi semua peminjaman, terbaru di atas, dengan keterlambatan dan jumlah perpanjangan', function () {
    $this->actingAs($this->user)
        ->get(route('history.index', HISTORY_ALL))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('history/index')
            ->has('bookings.data', 5)
            ->where('bookings.data.0.code', 'PMJ-202610-9003')
            ->where('bookings.data.4.code', 'PMJ-202609-9001')
            ->where('bookings.data.4.lateness_minutes', 150)
            ->where('bookings.data.4.extensions_count', 1)
            ->where('bookings.data.4.display_status', 'completed')
            ->where('bookings.data.1.code', 'PMJ-202610-9002')
            ->where('bookings.data.1.lateness_minutes', 180)
            ->where('query.period', [
                'from' => '2026-09-01',
                'to' => '2026-10-31',
                'default' => ['from' => '2026-10-01', 'to' => '2026-10-31'],
            ])
            ->has('filterOptions.status', 6)
            ->where('filterOptions.car', [
                ['value' => (string) $this->carA->id, 'label' => 'Daihatsu Terios (B 1111 AAA)'],
                ['value' => (string) $this->carB->id, 'label' => 'Toyota Avanza (D 2222 BBB)'],
            ])
            ->where('filterOptions.employee.0', ['value' => (string) $this->budi->id, 'label' => 'Budi Santoso (Teknik)'])
            ->where('exportFormats', [
                ['value' => 'xlsx', 'label' => 'Microsoft Excel (.xlsx)'],
                ['value' => 'csv', 'label' => 'CSV (.csv)'],
                ['value' => 'pdf', 'label' => 'Dokumen PDF (.pdf)'],
            ]),
        );
});

test('pencarian dan filter riwayat mengembalikan data yang benar', function (Closure $query, array $expected) {
    $this->actingAs($this->user)
        ->get(route('history.index', [...HISTORY_ALL, ...$query->call($this)]))
        ->assertInertia(fn (Assert $page) => expect(historyCodes($page))->toBe($expected));
})->with([
    'cari kode' => [fn () => ['search' => '202609-9002'], ['PMJ-202609-9002']],
    'cari nama karyawan' => [fn () => ['search' => 'rina'], ['PMJ-202609-9001', 'PMJ-202610-9002', 'PMJ-202610-9003']],
    'cari plat' => [fn () => ['search' => '2222'], ['PMJ-202609-9002', 'PMJ-202610-9002']],
    'status selesai' => [fn () => ['status' => ['completed']], ['PMJ-202609-9001', 'PMJ-202609-9002']],
    'status dibatalkan & terlambat' => [fn () => ['status' => ['cancelled', 'overdue']], ['PMJ-202610-9001', 'PMJ-202610-9002']],
    'mobil' => [fn () => ['car' => [(string) $this->carA->id]], ['PMJ-202609-9001', 'PMJ-202610-9001', 'PMJ-202610-9003']],
    'karyawan' => [fn () => ['employee' => [(string) $this->budi->id]], ['PMJ-202609-9002', 'PMJ-202610-9001']],
    'divisi' => [fn () => ['division' => ['Keuangan']], ['PMJ-202609-9001', 'PMJ-202610-9002', 'PMJ-202610-9003']],
    'terlambat kembali (termasuk yang masih terlambat)' => [fn () => ['lateness' => ['late']], ['PMJ-202609-9001', 'PMJ-202610-9002']],
    'tepat waktu' => [fn () => ['lateness' => ['on_time']], ['PMJ-202609-9002']],
    'periode september' => [fn () => ['from' => '2026-09-01', 'to' => '2026-09-30'], ['PMJ-202609-9001', 'PMJ-202609-9002']],
    'periode batas inklusif' => [fn () => ['from' => '2026-09-25', 'to' => '2026-10-05'], ['PMJ-202609-9002', 'PMJ-202610-9001']],
    'periode terbalik ditukar' => [fn () => ['from' => '2026-09-30', 'to' => '2026-09-01'], ['PMJ-202609-9001', 'PMJ-202609-9002']],
    'hanya dari' => [fn () => ['from' => '2026-10-19'], ['PMJ-202610-9002', 'PMJ-202610-9003']],
    'gabungan' => [fn () => ['employee' => [(string) $this->rina->id], 'status' => ['completed'], 'from' => '2026-09-01'], ['PMJ-202609-9001']],
]);

test('parameter tidak dikenal diabaikan', function () {
    $this->actingAs($this->user)
        ->get(route('history.index', [
            'status' => ['hapus'],
            'car' => ['99999'],
            'division' => ['Rahasia'],
            'lateness' => ['sangat'],
            'from' => '2026-02-31',
            'to' => 'kemarin',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('bookings.data', 5)
            ->where('query.filters', ['status' => [], 'car' => [], 'employee' => [], 'division' => [], 'lateness' => []])
            ->where('query.period', [
                'from' => null,
                'to' => null,
                'default' => ['from' => '2026-10-01', 'to' => '2026-10-31'],
            ]));
});

test('riwayat dibuka dengan periode bulan berjalan bila periode belum dipilih', function () {
    $this->actingAs($this->user)
        ->get(route('history.index'))
        ->assertInertia(function (Assert $page) {
            expect(historyCodes($page))->toBe(['PMJ-202610-9001', 'PMJ-202610-9002', 'PMJ-202610-9003']);

            $page->where('query.period', [
                'from' => '2026-10-01',
                'to' => '2026-10-31',
                'default' => ['from' => '2026-10-01', 'to' => '2026-10-31'],
            ]);
        });

    // Bulan berikutnya, periode bawaan ikut bergeser.
    $this->travelTo(CarbonImmutable::parse('2026-11-03 08:00:00'));

    $this->get(route('history.index'))
        ->assertInertia(fn (Assert $page) => $page->where('query.period.from', '2026-11-01')->has('bookings.data', 0));
});

test('ekspor tidak memakai periode bawaan: tanpa periode tetap ditolak', function () {
    $this->actingAs($this->user)
        ->get(route('history.export', ['format' => 'csv']))
        ->assertSessionHasErrors('period');
});

test('nomor urut halaman 2 dimulai dari 11', function () {
    Booking::factory()->count(10)->scheduled()->create();

    $this->actingAs($this->user)
        ->get(route('history.index', ['page' => 2, 'from' => '2026-01-01', 'to' => '2026-12-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('bookings.meta.from', 11)
            ->has('bookings.data', 5));
});

test('Admin dan Pengelola dapat membuka riwayat; tamu dan akun nonaktif tidak', function () {
    $this->actingAs(User::factory()->manager()->create())->get(route('history.index'))->assertOk();
    $this->actingAs($this->user)->get(route('history.index'))->assertOk();

    Auth::logout();
    $this->get(route('history.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->inactive()->create())
        ->get(route('history.index'))
        ->assertRedirect(route('login'));
});
