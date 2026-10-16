<?php

use App\Enums\ActivityEvent;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->admin()->create();
    $this->car = Car::factory()->create();
});

test('foto referensi diproses ulang menjadi JPEG maks. 1600 px dengan nama UUID', function () {
    $this->actingAs($this->user)
        ->post(route('cars.photos.store', ['car' => $this->car, 'position' => 1]), [
            'photo' => UploadedFile::fake()->image('Foto Asli Saya.png', 3000, 2000),
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $photo = CarPhoto::query()->sole();
    $stored = Storage::disk('public')->get($photo->path);
    [$width, $height, $type] = getimagesizefromstring($stored);

    expect($photo->path)->toMatch('/^cars\/[0-9a-f-]{36}\.jpg$/')
        ->and($photo->path)->not->toContain('Foto Asli')
        ->and($type)->toBe(IMAGETYPE_JPEG)
        ->and(max($width, $height))->toBe(1600)
        ->and(lastActivity()->event)->toBe(ActivityEvent::PhotoUpdated->value);
});

test('mengganti foto menghapus file lama', function () {
    $this->actingAs($this->user)->post(route('cars.photos.store', ['car' => $this->car, 'position' => 2]), [
        'photo' => UploadedFile::fake()->image('a.jpg', 800, 600),
    ]);
    $oldPath = CarPhoto::query()->sole()->path;

    $this->post(route('cars.photos.store', ['car' => $this->car, 'position' => 2]), [
        'photo' => UploadedFile::fake()->image('b.jpg', 800, 600),
    ]);

    $newPath = CarPhoto::query()->sole()->path;

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

test('file yang bukan gambar ditolak walau berekstensi .jpg', function () {
    $this->actingAs($this->user)
        ->post(route('cars.photos.store', ['car' => $this->car, 'position' => 1]), [
            'photo' => UploadedFile::fake()->create('foto.jpg', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('photo');

    expect(CarPhoto::query()->count())->toBe(0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('foto melebihi batas ukuran ditolak', function () {
    $this->actingAs($this->user)
        ->post(route('cars.photos.store', ['car' => $this->car, 'position' => 1]), [
            'photo' => UploadedFile::fake()->image('besar.jpg')->size(config('fleet.photos.max_upload_kb') + 1),
        ])
        ->assertSessionHasErrors(['photo' => 'Ukuran foto tidak boleh lebih dari 5120 kilobyte.']);
});

test('hanya slot foto 1 dan 2 yang tersedia', function () {
    $this->actingAs($this->user)
        ->post('/cars/'.$this->car->id.'/photos/3', ['photo' => UploadedFile::fake()->image('a.jpg')])
        ->assertNotFound();
});

test('tamu tidak dapat mengunggah foto', function () {
    $this->post(route('cars.photos.store', ['car' => $this->car, 'position' => 1]), [
        'photo' => UploadedFile::fake()->image('a.jpg'),
    ])->assertRedirect(route('login'));

    expect(CarPhoto::query()->count())->toBe(0);
});
