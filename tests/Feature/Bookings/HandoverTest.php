<?php

use App\Enums\ActivityEvent;
use App\Enums\BookingStatus;
use App\Enums\PhotoSource;
use App\Enums\PhotoStage;
use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\Car;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-10-20 08:00:00'));
    $this->user = User::factory()->admin()->create();
    $this->booking = Booking::factory()->scheduled()->create([
        'start_at' => CarbonImmutable::parse('2026-10-20 09:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 15:00'),
    ]);
});

/**
 * @return array<string, mixed>
 */
function handoverPayload(string $source = 'camera', array $overrides = []): array
{
    return [
        'photo' => UploadedFile::fake()->image('IMG_0001.jpg', 640, 480),
        'source' => $source,
        ...$overrides,
    ];
}

test('serah terima ambil menyimpan 1 foto privat dan mengaktifkan booking', function () {
    // Lebih awal dari waktu mulai (09.00) tetap boleh.
    $this->travelTo(CarbonImmutable::parse('2026-10-20 08:40:00'));

    $this->actingAs($this->user)
        ->post(route('bookings.pickup', $this->booking), handoverPayload('camera', [
            'photo' => UploadedFile::fake()->image('IMG_0001.jpg', 2400, 1800),
        ]))
        ->assertRedirect(route('bookings.show', $this->booking))
        ->assertSessionHas('success');

    $booking = $this->booking->refresh();
    $photo = BookingPhoto::query()->sole();
    [$width, $height, $type] = getimagesizefromstring(Storage::disk('local')->get($photo->path));

    expect($booking->status)->toBe(BookingStatus::Active)
        ->and($booking->picked_up_at->format('Y-m-d H:i'))->toBe('2026-10-20 08:40')
        ->and($booking->start_at->format('H:i'))->toBe('09:00')
        ->and($booking->picked_up_by)->toBe($this->user->id)
        ->and($photo->stage)->toBe(PhotoStage::Pickup)
        ->and($photo->source)->toBe(PhotoSource::Camera)
        ->and($photo->taken_at->format('Y-m-d H:i'))->toBe('2026-10-20 08:40')
        ->and($photo->path)->toMatch('/^bookings\/2026\/10\/[0-9a-f-]{36}\.jpg$/')
        ->and($photo->path)->not->toContain('IMG_0001')
        ->and($type)->toBe(IMAGETYPE_JPEG)
        ->and(max($width, $height))->toBe(1600)
        ->and(lastActivity()->event)->toBe(ActivityEvent::PickedUp->value);

    // Foto bukti tidak pernah masuk disk publik.
    expect(Storage::disk('local')->exists($photo->path))->toBeTrue();
});

test('serah terima kembali menyimpan foto dan catatan lalu menyelesaikan booking', function () {
    $this->actingAs($this->user)->post(route('bookings.pickup', $this->booking), handoverPayload());
    $this->travelTo(CarbonImmutable::parse('2026-10-20 16:10:00'));

    $this->actingAs($this->user)
        ->post(route('bookings.return', $this->booking), handoverPayload('upload', [
            'return_notes' => 'Baret baru di pintu kiri belakang',
        ]))
        ->assertSessionHasNoErrors();

    $booking = $this->booking->refresh();
    $photo = $booking->photos()->where('stage', PhotoStage::Return)->sole();

    expect($booking->status)->toBe(BookingStatus::Completed)
        ->and($booking->returned_at->format('Y-m-d H:i'))->toBe('2026-10-20 16:10')
        ->and($booking->return_notes)->toBe('Baret baru di pintu kiri belakang')
        ->and($booking->returned_by)->toBe($this->user->id)
        ->and($photo->source)->toBe(PhotoSource::Upload)
        ->and($booking->photos()->count())->toBe(2)
        ->and(lastActivity()->event)->toBe(ActivityEvent::Returned->value);
});

test('detail menampilkan kedua foto berdampingan tanpa path penyimpanan', function () {
    $this->actingAs($this->user)->post(route('bookings.pickup', $this->booking), handoverPayload());
    $this->post(route('bookings.return', $this->booking), handoverPayload('upload'));

    $this->get(route('bookings.show', $this->booking))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookings/show')
            ->where('booking.status', 'completed')
            ->where('booking.photos.pickup.url', route('bookings.photos.show', ['booking' => $this->booking->id, 'stage' => 'pickup']))
            ->where('booking.photos.pickup.source_label', 'Kamera')
            ->where('booking.photos.return.url', route('bookings.photos.show', ['booking' => $this->booking->id, 'stage' => 'return']))
            ->missing('booking.photos.pickup.path')
            ->where('booking.actions', ['edit' => false, 'cancel' => false, 'pickup' => false, 'extend' => false, 'return' => false]),
        );
});

test('serah terima tanpa foto atau dengan file bukan gambar ditolak', function (array $payload, string $field) {
    $this->actingAs($this->user)
        ->post(route('bookings.pickup', $this->booking), $payload)
        ->assertSessionHasErrors($field);

    expect($this->booking->refresh()->status)->toBe(BookingStatus::Scheduled)
        ->and(BookingPhoto::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'tanpa foto' => fn () => [['source' => 'camera'], 'photo'],
    'pdf berekstensi jpg' => fn () => [['photo' => UploadedFile::fake()->create('foto.jpg', 100, 'application/pdf'), 'source' => 'upload'], 'photo'],
    'sumber tidak dikenal' => fn () => [handoverPayload('scanner'), 'source'],
]);

test('mobil Maintenance tidak dapat diambil', function () {
    $this->booking->car->update(['operational_status' => 'maintenance']);

    $this->actingAs($this->user)
        ->post(route('bookings.pickup', $this->booking), handoverPayload())
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', "Mobil {$this->booking->car->plate_number} sedang Maintenance sehingga serah terima ambil tidak dapat dilakukan. Pindahkan peminjaman ke mobil lain.");

    // Foto yang sempat disimpan dibuang karena transaksi gagal.
    expect($this->booking->refresh()->status)->toBe(BookingStatus::Scheduled)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('serah terima ambil ditolak selama mobil belum kembali dari booking sebelumnya', function () {
    $car = $this->booking->car;
    $previous = Booking::factory()->for($car)->create([
        'status' => BookingStatus::Active,
        'start_at' => CarbonImmutable::parse('2026-10-20 05:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-20 07:00'),
        'picked_up_at' => CarbonImmutable::parse('2026-10-20 05:00'),
    ]);

    $this->actingAs($this->user)
        ->post(route('bookings.pickup', $this->booking), handoverPayload())
        ->assertInertiaFlash('toast.message', "Mobil {$car->plate_number} masih dipakai peminjaman {$previous->code} yang belum dikembalikan. Serah terima ambil dapat dilakukan setelah mobil dikembalikan.");

    expect($this->booking->refresh()->status)->toBe(BookingStatus::Scheduled);

    // Setelah mobil dikembalikan, serah terima ambil berhasil.
    $this->post(route('bookings.return', $previous), handoverPayload('upload'))->assertSessionHasNoErrors();
    $this->post(route('bookings.pickup', $this->booking), handoverPayload())->assertSessionHasNoErrors();

    expect($this->booking->refresh()->status)->toBe(BookingStatus::Active);
});

test('transisi yang tidak valid ditolak', function (string $state, string $route) {
    $booking = Booking::factory()->{$state}()->create();
    $before = $booking->status;

    $this->actingAs($this->user)
        ->post(route($route, $booking), handoverPayload())
        ->assertInertiaFlash('toast.type', 'error');

    expect($booking->refresh()->status)->toBe($before)
        ->and(BookingPhoto::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'ambil saat aktif' => ['active', 'bookings.pickup'],
    'ambil saat selesai' => ['completed', 'bookings.pickup'],
    'ambil saat dibatalkan' => ['cancelled', 'bookings.pickup'],
    'kembali saat dijadwalkan' => ['scheduled', 'bookings.return'],
    'kembali saat selesai' => ['completed', 'bookings.return'],
    'kembali saat dibatalkan' => ['cancelled', 'bookings.return'],
]);

test('mobil yang dipinjam tampil sebagai Dipinjam setelah serah terima ambil', function () {
    $this->actingAs($this->user)->post(route('bookings.pickup', $this->booking), handoverPayload());

    expect(Car::query()->withDisplayStatus()->findOrFail($this->booking->car_id)->displayStatus()->value)->toBe('borrowed');
});
