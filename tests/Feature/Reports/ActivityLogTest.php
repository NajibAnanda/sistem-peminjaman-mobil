<?php

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 08:00:00'));
    $this->manager = User::factory()->manager()->create(['name' => 'Pengelola Utama']);
    $this->admin = User::factory()->admin()->create(['name' => 'Admin Satu']);
});

/**
 * @return list<string>
 */
function activityDescriptions(Assert $page): array
{
    return collect($page->toArray()['props']['activities']['data'])->pluck('description')->all();
}

test('perubahan jadwal ditampilkan dalam bahasa yang mudah dipahami', function () {
    $oldCar = Car::factory()->create(['plate_number' => 'B 1111 AAA']);
    $newCar = Car::factory()->create(['plate_number' => 'D 2222 BBB']);
    $booking = Booking::factory()->scheduled()->for($oldCar)->create([
        'start_at' => CarbonImmutable::parse('2026-10-21 09:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-21 12:00'),
    ]);

    $this->actingAs($this->admin)->put(route('bookings.update', $booking), [
        'employee_id' => $booking->employee_id,
        'car_id' => $newCar->id,
        'start_at' => '2026-10-21T09:00',
        'planned_return_at' => '2026-10-21T15:30',
        'purpose' => $booking->purpose,
    ])->assertSessionHasNoErrors();

    $this->actingAs($this->manager)
        ->get(route('activity-log.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('activity-log/index')
            ->where('activities.data.0.description', "Mengubah jadwal peminjaman {$booking->code}")
            ->where('activities.data.0.causer_name', 'Admin Satu')
            ->where('activities.data.0.type_label', 'Peminjaman')
            ->where('activities.data.0.event_label', 'Diubah')
            ->where('activities.data.0.subject_label', $booking->code)
            ->where('activities.data.0.changes', [
                ['field' => 'car_id', 'label' => 'Mobil', 'old' => 'B 1111 AAA', 'new' => 'D 2222 BBB'],
                ['field' => 'planned_return_at', 'label' => 'Rencana waktu kembali', 'old' => '21 Okt 2026, 12.00', 'new' => '21 Okt 2026, 15.30'],
            ])
            ->where('filterOptions.type.0', ['value' => 'staff', 'label' => 'Akun Petugas']),
        );
});

test('status, aktif/nonaktif, dan keterangan tambahan ditampilkan dengan label', function () {
    $booking = Booking::factory()->scheduled()->create();

    $this->actingAs($this->admin)
        ->post(route('bookings.cancel', $booking), ['reason' => 'Kegiatan ditunda'])
        ->assertSessionHasNoErrors();
    $this->post(route('cars.deactivate', Car::factory()->create()));

    $this->actingAs($this->manager)
        ->get(route('activity-log.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('activities.data.0.changes', [
                ['field' => 'is_active', 'label' => 'Status aktif', 'old' => 'Aktif', 'new' => 'Nonaktif'],
            ])
            ->where('activities.data.1.changes.0', ['field' => 'status', 'label' => 'Status', 'old' => 'Dijadwalkan', 'new' => 'Dibatalkan'])
            ->where('activities.data.1.changes.1', ['field' => 'cancellation_reason', 'label' => 'Alasan pembatalan', 'old' => null, 'new' => 'Kegiatan ditunda']));
});

test('log ekspor menampilkan format dan jumlah baris', function () {
    $this->actingAs($this->admin)->get(route('history.export', ['format' => 'csv']));

    $this->actingAs($this->manager)
        ->get(route('activity-log.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('activities.data.0.type_label', 'Laporan')
            ->where('activities.data.0.event_label', 'Diekspor')
            ->where('activities.data.0.details', fn ($details) => collect($details)->pluck('value', 'label')->only(['Format file', 'Jumlah baris'])->all() === [
                'Format file' => 'CSV',
                'Jumlah baris' => '0',
            ]));
});

test('filter jenis data, petugas, periode, dan pencarian', function (Closure $query, array $expected) {
    activity(ActivityLogName::Car)->causedBy($this->admin)->event(ActivityEvent::Created->value)->log('Menambah mobil A');
    $this->travelTo(CarbonImmutable::parse('2026-10-22 08:00:00'));
    activity(ActivityLogName::Employee)->causedBy($this->manager)->event(ActivityEvent::Created->value)->log('Menambah karyawan B');
    activity(ActivityLogName::Car)->causedBy($this->manager)->event(ActivityEvent::Updated->value)->log('Mengubah data mobil A');

    $this->actingAs($this->manager)
        ->get(route('activity-log.index', $query->call($this)))
        ->assertInertia(fn (Assert $page) => expect(activityDescriptions($page))->toBe($expected));
})->with([
    'jenis data mobil' => [fn () => ['type' => ['car']], ['Mengubah data mobil A', 'Menambah mobil A']],
    'petugas admin' => [fn () => ['staff' => [(string) $this->admin->id]], ['Menambah mobil A']],
    'periode 22 Okt' => [fn () => ['from' => '2026-10-22', 'to' => '2026-10-22'], ['Mengubah data mobil A', 'Menambah karyawan B']],
    'pencarian' => [fn () => ['search' => 'karyawan'], ['Menambah karyawan B']],
    'tidak dikenal diabaikan' => [fn () => ['type' => ['rahasia'], 'staff' => ['99999'], 'from' => 'besok'], ['Mengubah data mobil A', 'Menambah karyawan B', 'Menambah mobil A']],
]);

test('nomor urut halaman 2 dimulai dari 11', function () {
    foreach (range(1, 15) as $i) {
        activity(ActivityLogName::Car)->causedBy($this->admin)->log("Aktivitas {$i}");
    }

    $this->actingAs($this->manager)
        ->get(route('activity-log.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->where('activities.meta.from', 11)->has('activities.data', 5));
});

test('Admin ditolak mengakses log aktivitas', function () {
    $this->actingAs($this->admin)->get(route('activity-log.index'))->assertForbidden();
});

test('tamu dan akun nonaktif tidak dapat membuka log aktivitas', function () {
    $this->get(route('activity-log.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->manager()->inactive()->create())
        ->get(route('activity-log.index'))
        ->assertRedirect(route('login'));
});

test('log tidak dapat diubah atau dihapus lewat aplikasi', function (string $method) {
    activity(ActivityLogName::Car)->causedBy($this->admin)->log('Menambah mobil A');

    $this->actingAs($this->manager)
        ->{$method}('/activity-log/1')
        ->assertNotFound();

    $this->assertDatabaseCount('activity_log', 1);
})->with(['put', 'patch', 'delete']);
