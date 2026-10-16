<?php

use App\Enums\ActivityEvent;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 08:00:00'));
    $this->user = User::factory()->admin()->create();
    $this->car = Car::factory()->create();
    $this->employee = Employee::factory()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function schedulePayload(Car $car, Employee $employee, array $overrides = []): array
{
    return [
        'employee_id' => $employee->id,
        'car_id' => $car->id,
        'start_at' => '2026-10-20T09:00',
        'planned_return_at' => '2026-10-20T15:00',
        'purpose' => 'Kunjungan klien di Bekasi',
        ...$overrides,
    ];
}

function existingBooking(Car $car, string $start, string $end, BookingStatus $status = BookingStatus::Scheduled): Booking
{
    return Booking::factory()->for($car)->create([
        'status' => $status,
        'start_at' => CarbonImmutable::parse($start),
        'planned_return_at' => CarbonImmutable::parse($end),
        'picked_up_at' => $status === BookingStatus::Active ? CarbonImmutable::parse($start) : null,
    ]);
}

test('admin dapat menjadwalkan peminjaman', function () {
    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee));

    $booking = Booking::query()->sole();

    $response->assertRedirect(route('bookings.show', $booking))
        ->assertSessionHas('success', "Peminjaman {$booking->code} berhasil dijadwalkan.");

    expect($booking->code)->toBe('PMJ-202610-0001')
        ->and($booking->status)->toBe(BookingStatus::Scheduled)
        ->and($booking->start_at->format('Y-m-d H:i'))->toBe('2026-10-20 09:00')
        ->and($booking->planned_return_at->format('Y-m-d H:i'))->toBe('2026-10-20 15:00')
        ->and($booking->created_by)->toBe($this->user->id)
        ->and(lastActivity()->event)->toBe(ActivityEvent::Created->value)
        ->and(lastActivity()->subject_id)->toBe($booking->id);
});

test('field wajib dan rencana kembali setelah waktu mulai divalidasi', function () {
    $this->actingAs($this->user)
        ->post(route('bookings.store'), [])
        ->assertSessionHasErrors(['employee_id', 'car_id', 'start_at', 'planned_return_at', 'purpose']);

    $this->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
        'planned_return_at' => '2026-10-20T09:00',
    ]))->assertSessionHasErrors(['planned_return_at' => 'Rencana waktu kembali harus setelah waktu mulai.']);

    expect(Booking::query()->count())->toBe(0);
});

test('waktu mulai di masa lalu diterima bila masih dalam toleransi', function () {
    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
            'start_at' => '2026-10-20T07:45',
        ]))
        ->assertSessionHasNoErrors();

    expect(Booking::query()->count())->toBe(1);
});

test('waktu mulai di luar toleransi ditolak', function () {
    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
            'start_at' => '2026-10-20T07:44',
        ]))
        ->assertSessionHasErrors(['start_at' => 'Waktu mulai tidak boleh lebih dari 15 menit yang lalu (paling awal 20 Okt 2026, 07.45).']);

    expect(Booking::query()->count())->toBe(0);
});

test('toleransi waktu mulai mengikuti config', function () {
    config(['fleet.booking.start_tolerance_minutes' => 60]);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
            'start_at' => '2026-10-20T07:10',
        ]))
        ->assertSessionHasNoErrors();
});

test('jadwal yang bentrok ditolak dan menyebut peminjaman yang bertabrakan', function (string $start, string $end) {
    $existing = existingBooking($this->car, '2026-10-20 10:00', '2026-10-20 14:00');

    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
            'start_at' => $start,
            'planned_return_at' => $end,
        ]))
        ->assertSessionHasErrors(['car_id' => "Jadwal bentrok dengan peminjaman lain untuk mobil ini: {$existing->code} ({$existing->employee->name}, 20 Okt 2026, 10.00 – 20 Okt 2026, 14.00). Pilih waktu atau mobil lain."]);

    expect(Booking::query()->count())->toBe(1);
})->with([
    'menimpa awal' => ['2026-10-20T09:00', '2026-10-20T11:00'],
    'menimpa akhir' => ['2026-10-20T13:00', '2026-10-20T16:00'],
    'di dalam' => ['2026-10-20T11:00', '2026-10-20T12:00'],
    'melingkupi' => ['2026-10-20T09:00', '2026-10-20T16:00'],
    'sama persis' => ['2026-10-20T10:00', '2026-10-20T14:00'],
]);

test('jadwal yang tepat bersentuhan tidak dianggap bentrok', function (string $start, string $end) {
    existingBooking($this->car, '2026-10-20 10:00', '2026-10-20 14:00');

    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
            'start_at' => $start,
            'planned_return_at' => $end,
        ]))
        ->assertSessionHasNoErrors();

    expect(Booking::query()->count())->toBe(2);
})->with([
    'berakhir saat yang lain mulai' => ['2026-10-20T08:00', '2026-10-20T10:00'],
    'mulai saat yang lain berakhir' => ['2026-10-20T14:00', '2026-10-20T18:00'],
]);

test('peminjaman selesai atau dibatalkan tidak menghalangi jadwal', function (BookingStatus $status) {
    existingBooking($this->car, '2026-10-20 08:00', '2026-10-20 16:00', $status);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee))
        ->assertSessionHasNoErrors();
})->with([BookingStatus::Completed, BookingStatus::Cancelled]);

test('mobil yang terlambat dianggap terpakai sampai sekarang', function () {
    // Rencana kembali 07.00 sudah lewat; mobil belum kembali (Terlambat).
    $overdue = existingBooking($this->car, '2026-10-20 06:00', '2026-10-20 07:00', BookingStatus::Active);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
            'start_at' => '2026-10-20T07:50',
            'planned_return_at' => '2026-10-20T12:00',
        ]))
        ->assertSessionHasErrors('car_id');

    expect(session('errors')->first('car_id'))->toContain($overdue->code);
});

test('jadwal mendatang untuk mobil yang terlambat tetap boleh dibuat', function () {
    existingBooking($this->car, '2026-10-20 06:00', '2026-10-20 07:00', BookingStatus::Active);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
            'start_at' => '2026-10-20T10:00',
            'planned_return_at' => '2026-10-20T12:00',
        ]))
        ->assertSessionHasNoErrors();

    expect(Booking::query()->count())->toBe(2);
});

test('mobil nonaktif atau Maintenance tidak dapat dijadwalkan', function (string $state, string $reason) {
    $car = Car::factory()->{$state}()->create();

    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($car, $this->employee))
        ->assertSessionHasErrors(['car_id' => "Mobil {$car->plate_number} sedang {$reason} sehingga tidak dapat dijadwalkan."]);
})->with([
    'nonaktif' => ['inactive', 'nonaktif'],
    'maintenance' => ['maintenance', 'Maintenance'],
]);

test('karyawan nonaktif tidak dapat dijadwalkan', function () {
    $employee = Employee::factory()->inactive()->create();

    $this->actingAs($this->user)
        ->post(route('bookings.store'), schedulePayload($this->car, $employee))
        ->assertSessionHasErrors(['employee_id' => "Karyawan {$employee->name} sudah nonaktif sehingga tidak dapat dijadwalkan."]);
});

test('kode booking unik dan berurutan per bulan', function () {
    $this->actingAs($this->user);
    $otherCar = Car::factory()->create();

    $this->post(route('bookings.store'), schedulePayload($this->car, $this->employee));
    $this->post(route('bookings.store'), schedulePayload($otherCar, $this->employee));
    $this->post(route('bookings.store'), schedulePayload($this->car, $this->employee, [
        'start_at' => '2026-11-02T09:00',
        'planned_return_at' => '2026-11-02T12:00',
    ]));

    // Nomor mengikuti bulan saat jadwal dicatat, bukan bulan waktu mulai.
    $this->travelTo(CarbonImmutable::parse('2026-11-01 08:00:00'));
    $this->post(route('bookings.store'), schedulePayload($otherCar, $this->employee, [
        'start_at' => '2026-11-01T09:00',
        'planned_return_at' => '2026-11-01T12:00',
    ]));

    expect(Booking::query()->orderBy('id')->pluck('code')->all())->toBe([
        'PMJ-202610-0001',
        'PMJ-202610-0002',
        'PMJ-202610-0003',
        'PMJ-202611-0001',
    ]);
});

test('form jadwal menandai ketersediaan mobil pada rentang yang dipilih', function () {
    $busy = Car::factory()->create(['plate_number' => 'B 2000 BBB']);
    $conflict = existingBooking($busy, '2026-10-20 10:00', '2026-10-20 14:00');
    $maintenance = Car::factory()->maintenance()->create(['plate_number' => 'B 3000 CCC']);
    Car::factory()->inactive()->create();
    Employee::factory()->inactive()->create();

    $this->actingAs($this->user)
        ->get(route('bookings.create', ['start_at' => '2026-10-20T09:00', 'planned_return_at' => '2026-10-20T11:00']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookings/form')
            ->where('booking', null)
            // Karyawan uji + karyawan milik booking yang bentrok; yang nonaktif tidak.
            ->has('employees', 2)
            ->has('cars', 3)
            ->where('startToleranceMinutes', 15)
            // Urutan mobil mengikuti plat (acak dari factory), jadi dibandingkan per id.
            ->where('cars', fn ($cars) => collect($cars)->mapWithKeys(fn ($car) => [
                $car['id'] => [$car['available'], $car['reason']],
            ])->sortKeys()->all() === collect([
                $busy->id => [false, "Bentrok dengan {$conflict->code}"],
                $maintenance->id => [false, 'Maintenance'],
                $this->car->id => [true, null],
            ])->sortKeys()->all()),
        );
});

test('tanpa rentang waktu ketersediaan mobil belum ditandai dan rentang tidak valid diabaikan', function (array $query) {
    $this->actingAs($this->user)
        ->get(route('bookings.create', $query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('cars.0.available', null));
})->with([
    'tanpa query' => [[]],
    'format salah' => [['start_at' => 'kemarin', 'planned_return_at' => 'besok']],
    'kembali sebelum mulai' => [['start_at' => '2026-10-20T12:00', 'planned_return_at' => '2026-10-20T09:00']],
]);
