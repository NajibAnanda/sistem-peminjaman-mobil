<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Validasi tambahan peminjaman: durasi min./maks., panjang tujuan & alasan,
 * bentrok per karyawan, cek ulang saat serah terima ambil, dan pesan saat
 * data sudah diubah petugas lain.
 */

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 08:00:00'));
    $this->user = User::factory()->admin()->create();
    $this->car = Car::factory()->create(['plate_number' => 'B 1111 AAA']);
    $this->employee = Employee::factory()->create(['name' => 'Rina Kartika']);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validationPayload(Car $car, Employee $employee, array $overrides = []): array
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

test('durasi peminjaman kurang dari batas minimal ditolak', function () {
    $this->actingAs($this->user)
        ->post(route('bookings.store'), validationPayload($this->car, $this->employee, [
            'planned_return_at' => '2026-10-20T09:15',
        ]))
        ->assertSessionHasErrors(['planned_return_at' => 'Durasi peminjaman minimal 30 menit.']);

    // Tepat 30 menit masih boleh.
    $this->post(route('bookings.store'), validationPayload($this->car, $this->employee, [
        'planned_return_at' => '2026-10-20T09:30',
    ]))->assertSessionHasNoErrors();
});

test('tanpa batas durasi maksimal bila FLEET_MAX_BOOKING_DAYS kosong', function () {
    config(['fleet.booking.max_duration_days' => null]);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), validationPayload($this->car, $this->employee, [
            'planned_return_at' => '2027-01-18T09:00',
        ]))
        ->assertSessionHasNoErrors();

    expect(Booking::query()->sole()->planned_return_at->format('Y-m-d'))->toBe('2027-01-18');
});

test('durasi maksimal ditolak bila kantor menetapkan batas di config', function () {
    config(['fleet.booking.max_duration_days' => 30]);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), validationPayload($this->car, $this->employee, [
            'planned_return_at' => '2026-11-19T09:01',
        ]))
        ->assertSessionHasErrors(['planned_return_at' => 'Durasi peminjaman maksimal 30 hari sejak waktu mulai.']);

    $this->post(route('bookings.store'), validationPayload($this->car, $this->employee, [
        'planned_return_at' => '2026-11-19T09:00',
    ]))->assertSessionHasNoErrors();
});

test('batas durasi mengikuti config', function () {
    config(['fleet.booking.min_duration_minutes' => 60, 'fleet.booking.max_duration_days' => 1]);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), validationPayload($this->car, $this->employee, [
            'planned_return_at' => '2026-10-20T09:45',
        ]))
        ->assertSessionHasErrors(['planned_return_at' => 'Durasi peminjaman minimal 60 menit.']);

    $this->post(route('bookings.store'), validationPayload($this->car, $this->employee, [
        'planned_return_at' => '2026-10-21T10:00',
    ]))->assertSessionHasErrors(['planned_return_at' => 'Durasi peminjaman maksimal 1 hari sejak waktu mulai.']);
});

test('durasi minimal juga berlaku saat edit jadwal', function () {
    $booking = Booking::factory()->scheduled()->create([
        'car_id' => $this->car->id,
        'employee_id' => $this->employee->id,
        'start_at' => CarbonImmutable::parse('2026-10-20 09:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 15:00'),
    ]);

    $this->actingAs($this->user)
        ->put(route('bookings.update', $booking), validationPayload($this->car, $this->employee, [
            'planned_return_at' => '2026-10-20T09:10',
        ]))
        ->assertSessionHasErrors(['planned_return_at' => 'Durasi peminjaman minimal 30 menit.']);
});

test('tujuan terlalu pendek ditolak', function () {
    $this->actingAs($this->user)
        ->post(route('bookings.store'), validationPayload($this->car, $this->employee, ['purpose' => '  ab  ']))
        ->assertSessionHasErrors(['purpose' => 'Tujuan minimal 5 karakter.']);

    expect(Booking::query()->count())->toBe(0);
});

test('karyawan tidak boleh memiliki dua peminjaman yang waktunya tumpang tindih', function () {
    $existing = Booking::factory()->scheduled()->create([
        'code' => 'PMJ-202610-0100',
        'employee_id' => $this->employee->id,
        'start_at' => CarbonImmutable::parse('2026-10-20 13:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 17:00'),
    ]);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), validationPayload($this->car, $this->employee))
        ->assertSessionHasErrors(['employee_id' => "Rina Kartika sudah memiliki peminjaman lain pada waktu yang sama: PMJ-202610-0100 ({$existing->car->plate_number}, 20 Okt 2026, 13.00 – 20 Okt 2026, 17.00). Pilih waktu lain."]);

    // Bersambung tepat di batas tidak dianggap tumpang tindih.
    $this->post(route('bookings.store'), validationPayload($this->car, $this->employee, [
        'planned_return_at' => '2026-10-20T13:00',
    ]))->assertSessionHasNoErrors();

    // Peminjaman yang sudah selesai/dibatalkan tidak menghalangi.
    $existing->update(['status' => BookingStatus::Cancelled]);
    Booking::query()->where('planned_return_at', '2026-10-20 13:00')->update(['status' => BookingStatus::Cancelled]);

    $this->post(route('bookings.store'), validationPayload($this->car, $this->employee))->assertSessionHasNoErrors();
});

test('bentrok karyawan dicek saat edit jadwal dan perpanjangan', function () {
    $other = Booking::factory()->scheduled()->create([
        'employee_id' => $this->employee->id,
        'start_at' => CarbonImmutable::parse('2026-10-21 08:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-21 12:00'),
    ]);
    $booking = Booking::factory()->scheduled()->create([
        'car_id' => $this->car->id,
        'employee_id' => $this->employee->id,
        'start_at' => CarbonImmutable::parse('2026-10-20 09:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 15:00'),
    ]);

    $this->actingAs($this->user)
        ->put(route('bookings.update', $booking), validationPayload($this->car, $this->employee, [
            'planned_return_at' => '2026-10-21T09:00',
        ]))
        ->assertSessionHasErrors('employee_id');

    // Mengganti ke karyawan yang sedang memakai mobil lain pada jam itu juga ditolak.
    $busy = Employee::factory()->create();
    Booking::factory()->scheduled()->create([
        'employee_id' => $busy->id,
        'start_at' => CarbonImmutable::parse('2026-10-20 10:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 11:00'),
    ]);

    $this->put(route('bookings.update', $booking), validationPayload($this->car, $busy))
        ->assertSessionHasErrors('employee_id');

    // Perpanjangan yang menabrak peminjaman karyawan berikutnya.
    $booking->update(['status' => BookingStatus::Active, 'picked_up_at' => now()]);

    $this->post(route('bookings.extend', $booking), [
        'new_planned_return_at' => '2026-10-21T09:00',
        'reason' => 'Rapat dengan klien diperpanjang',
    ])->assertSessionHasErrors('new_planned_return_at');

    expect($booking->refresh()->planned_return_at->format('Y-m-d H:i'))->toBe('2026-10-20 15:00')
        ->and($other->refresh()->status)->toBe(BookingStatus::Scheduled);
});

test('perpanjangan panjang diterima tanpa batas, dan ditolak bila batas ditetapkan', function () {
    $active = Booking::factory()->create([
        'status' => BookingStatus::Active,
        'start_at' => CarbonImmutable::parse('2026-10-19 08:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 12:00'),
        'picked_up_at' => CarbonImmutable::parse('2026-10-19 08:00'),
    ]);
    $payload = [
        'new_planned_return_at' => '2026-11-18T08:30',
        'reason' => 'Penugasan luar kota diperpanjang',
    ];

    config(['fleet.booking.max_duration_days' => 30]);

    $this->actingAs($this->user)
        ->post(route('bookings.extend', $active), $payload)
        ->assertSessionHasErrors(['new_planned_return_at' => 'Durasi peminjaman maksimal 30 hari sejak waktu mulai.']);

    expect(BookingExtension::query()->count())->toBe(0);

    config(['fleet.booking.max_duration_days' => null]);

    $this->post(route('bookings.extend', $active), $payload)->assertSessionHasNoErrors();

    expect($active->refresh()->planned_return_at->format('Y-m-d H:i'))->toBe('2026-11-18 08:30');
});

test('alasan pembatalan dan perpanjangan minimal 10 karakter', function () {
    $scheduled = Booking::factory()->scheduled()->create();
    $active = Booking::factory()->create([
        'status' => BookingStatus::Active,
        'start_at' => CarbonImmutable::parse('2026-10-20 07:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 12:00'),
        'picked_up_at' => CarbonImmutable::parse('2026-10-20 07:00'),
    ]);

    $this->actingAs($this->user)
        ->post(route('bookings.cancel', $scheduled), ['reason' => 'Batal'])
        ->assertSessionHasErrors(['reason' => 'Alasan minimal 10 karakter.']);

    $this->post(route('bookings.extend', $active), [
        'new_planned_return_at' => '2026-10-20T14:00',
        'reason' => 'Lama',
    ])->assertSessionHasErrors(['reason' => 'Alasan minimal 10 karakter.']);

    expect($scheduled->refresh()->status)->toBe(BookingStatus::Scheduled)
        ->and(BookingExtension::query()->count())->toBe(0);
});

test('serah terima ambil ditolak bila karyawan atau mobil sudah nonaktif', function (string $inactive) {
    Storage::fake('local');
    $booking = Booking::factory()->scheduled()->create([
        'car_id' => $this->car->id,
        'employee_id' => $this->employee->id,
        'start_at' => CarbonImmutable::parse('2026-10-20 09:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 15:00'),
    ]);
    // Data lama/impor: dinonaktifkan langsung di database, melewati aturan aplikasi.
    ($inactive === 'karyawan' ? $this->employee : $this->car)->forceFill(['is_active' => false])->save();

    $this->actingAs($this->user)
        ->post(route('bookings.pickup', $booking), [
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'source' => 'upload',
        ])
        ->assertInertiaFlash('toast.message', $inactive === 'karyawan'
            ? 'Karyawan Rina Kartika sudah nonaktif sehingga serah terima ambil tidak dapat dilakukan. Batalkan peminjaman ini atau ganti karyawannya lewat Edit Jadwal.'
            : 'Mobil B 1111 AAA sudah nonaktif sehingga serah terima ambil tidak dapat dilakukan. Pindahkan peminjaman ke mobil lain lewat Edit Jadwal.');

    expect($booking->refresh()->status)->toBe(BookingStatus::Scheduled)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with(['karyawan', 'mobil']);

test('serah terima ganda memberi pesan bahwa data sudah diubah', function () {
    Storage::fake('local');
    $booking = Booking::factory()->scheduled()->create([
        'start_at' => CarbonImmutable::parse('2026-10-20 09:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 15:00'),
    ]);
    $payload = fn (): array => ['photo' => UploadedFile::fake()->image('foto.jpg'), 'source' => 'upload'];

    $this->actingAs($this->user)->post(route('bookings.pickup', $booking), $payload())->assertSessionHas('success');

    // Petugas lain (atau klik kedua) dengan halaman lama.
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('bookings.pickup', $booking), $payload())
        ->assertInertiaFlash('toast.message', 'Peminjaman ini sudah berstatus Aktif sehingga tidak dapat diubah menjadi Aktif. Data kemungkinan baru saja diubah petugas lain. Muat ulang halaman untuk melihat data terbaru.');

    expect($booking->photos()->count())->toBe(1);
});

test('mengaktifkan atau menonaktifkan data yang statusnya sudah berubah memberi pesan muat ulang', function () {
    $employee = Employee::factory()->inactive()->create();

    $this->actingAs($this->user)
        ->post(route('employees.deactivate', $employee))
        ->assertInertiaFlash('toast.message', 'Karyawan ini sudah nonaktif. Muat ulang halaman untuk melihat data terbaru.');
});

test('aturan durasi dibagikan ke form sebagai petunjuk', function () {
    config(['fleet.booking.max_duration_days' => null, 'fleet.booking.long_booking_warning_days' => 7]);

    $this->actingAs($this->user)
        ->get(route('bookings.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('bookingRules.maxDurationDays', null)
            ->where('bookingRules.longBookingWarningDays', 7)
            ->where('bookingRules.minDurationMinutes', 30));

    config(['fleet.booking.max_duration_days' => 14]);

    $this->get(route('bookings.create'))
        ->assertInertia(fn (Assert $page) => $page->where('bookingRules.maxDurationDays', 14));
});

test('form jadwal hanya menampilkan karyawan dan mobil aktif; Maintenance tampil tetapi tidak tersedia', function () {
    Employee::factory()->inactive()->create(['name' => 'Karyawan Nonaktif']);
    Car::factory()->inactive()->create(['plate_number' => 'B 9999 ZZZ']);
    Car::factory()->maintenance()->create(['plate_number' => 'B 8888 YYY']);

    $this->actingAs($this->user)
        ->get(route('bookings.create', ['start_at' => '2026-10-20T09:00', 'planned_return_at' => '2026-10-20T15:00']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('employees', fn ($employees) => collect($employees)->pluck('name')->all() === ['Rina Kartika'])
            ->where('cars', fn ($cars) => collect($cars)->pluck('reason', 'plate_number')->all() === [
                'B 1111 AAA' => null,
                'B 8888 YYY' => 'Maintenance',
            ]));
});
