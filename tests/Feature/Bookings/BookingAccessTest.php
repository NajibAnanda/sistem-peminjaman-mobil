<?php

use App\Enums\PhotoStage;
use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->booking = Booking::factory()->active()->create();
    $this->photo = BookingPhoto::factory()->for($this->booking)->create(['stage' => PhotoStage::Pickup]);
    Storage::disk('local')->put($this->photo->path, 'isi-foto');
});

/**
 * Semua endpoint peminjaman: [method, nama route].
 */
dataset('endpoint peminjaman', [
    'daftar' => ['get', 'bookings.index'],
    'form baru' => ['get', 'bookings.create'],
    'simpan' => ['post', 'bookings.store'],
    'detail' => ['get', 'bookings.show'],
    'form edit' => ['get', 'bookings.edit'],
    'ubah' => ['put', 'bookings.update'],
    'ambil' => ['post', 'bookings.pickup'],
    'kembali' => ['post', 'bookings.return'],
    'perpanjang' => ['post', 'bookings.extend'],
    'batal' => ['post', 'bookings.cancel'],
    'foto' => ['get', 'bookings.photos.show'],
]);

function bookingRoute(string $name, Booking $booking): string
{
    return match ($name) {
        'bookings.index', 'bookings.create', 'bookings.store' => route($name),
        'bookings.photos.show' => route($name, ['booking' => $booking, 'stage' => 'pickup']),
        default => route($name, $booking),
    };
}

test('tamu diarahkan ke login', function (string $method, string $name) {
    $this->{$method}(bookingRoute($name, $this->booking))->assertRedirect(route('login'));
})->with('endpoint peminjaman');

test('akun nonaktif dikeluarkan', function (string $method, string $name) {
    $this->actingAs(User::factory()->manager()->inactive()->create())
        ->{$method}(bookingRoute($name, $this->booking))
        ->assertRedirect(route('login'));

    $this->assertGuest();
})->with('endpoint peminjaman');

test('Admin dan Pengelola dapat membuka halaman peminjaman', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $scheduled = Booking::factory()->scheduled()->create();

    $this->actingAs($user);
    $this->get(route('bookings.index'))->assertOk();
    $this->get(route('bookings.create'))->assertOk();
    $this->get(route('bookings.show', $this->booking))->assertOk();
    $this->get(route('bookings.edit', $scheduled))->assertOk();
})->with(['admin', 'manager']);

test('foto bukti hanya disajikan untuk petugas yang login', function (string $role) {
    $response = $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('bookings.photos.show', ['booking' => $this->booking, 'stage' => 'pickup']));

    $response->assertOk();

    expect($response->streamedContent())->toBe('isi-foto')
        ->and($response->headers->get('Cache-Control'))->toContain('private');
})->with(['admin', 'manager']);

test('foto bukti tidak dapat diakses tamu', function () {
    $this->get(route('bookings.photos.show', ['booking' => $this->booking, 'stage' => 'pickup']))
        ->assertRedirect(route('login'));
});

test('foto tahap yang belum ada atau tahap tidak dikenal menghasilkan 404', function (string $stage) {
    $this->actingAs(User::factory()->admin()->create())
        ->get("/bookings/{$this->booking->id}/photos/{$stage}")
        ->assertNotFound();
})->with(['return', 'odometer']);

test('form edit booking yang bukan Dijadwalkan diarahkan ke detail', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('bookings.edit', $this->booking))
        ->assertRedirect(route('bookings.show', $this->booking))
        ->assertSessionHas('error', 'Jadwal hanya dapat diedit selama peminjaman berstatus Dijadwalkan (status saat ini: Aktif).');
});
