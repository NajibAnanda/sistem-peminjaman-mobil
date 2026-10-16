<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/** Satu-satunya route yang boleh dibuka tanpa login (prd.md NFR-03). */
const PUBLIC_ROUTES = ['home', 'login', 'login.store', 'two-factor.login', 'two-factor.login.store'];

const PUBLIC_URIS = ['up'];

test('setiap route aplikasi mewajibkan login kecuali login, tantangan 2FA, dan health check', function () {
    $unprotected = collect(Route::getRoutes()->getRoutes())
        ->reject(fn (RoutingRoute $route) => in_array($route->getName(), PUBLIC_ROUTES, true)
            || in_array($route->uri(), PUBLIC_URIS, true))
        ->reject(fn (RoutingRoute $route) => collect($route->gatherMiddleware())
            ->contains(fn ($middleware) => is_string($middleware) && ($middleware === 'auth' || str_starts_with($middleware, 'auth:'))))
        ->map(fn (RoutingRoute $route) => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    // Route baru yang lupa diberi middleware auth akan muncul di sini.
    expect($unprotected)->toBe([]);
});

test('tamu yang membuka halaman mana pun diarahkan ke login', function () {
    $guestReachable = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true))
        ->reject(fn (RoutingRoute $route) => in_array($route->getName(), PUBLIC_ROUTES, true)
            || in_array($route->uri(), PUBLIC_URIS, true))
        // Parameter diisi angka sembarang: tamu harus ditolak sebelum data dicari.
        ->map(fn (RoutingRoute $route) => '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri()))
        ->filter(fn (string $uri) => ! in_array($this->get($uri)->getStatusCode(), [302, 401], true))
        ->values()
        ->all();

    expect($guestReachable)->toBe([]);
});

test('Inertia DevTools tidak aktif sehingga rekaman request tidak bisa dibaca', function () {
    $devtoolsRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => str_starts_with($route->uri(), '_inertia/devtools'));

    expect(config('inertia.devtools.enabled'))->toBeFalse()
        ->and($devtoolsRoutes)->toBeEmpty();

    $this->get('/_inertia/devtools/entries')->assertNotFound();
});

test('perlindungan CSRF terpasang di grup web', function () {
    expect(app(Kernel::class)->getMiddlewareGroups()['web'])->toContain(PreventRequestForgery::class);
});

test('halaman yang tidak ada menampilkan halaman error ramah tanpa detail teknis', function (bool $loggedIn) {
    config(['app.debug' => false]);

    if ($loggedIn) {
        $this->actingAs(User::factory()->admin()->create());
    }

    $response = $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 404)
            ->where('title', 'Halaman tidak ditemukan'));

    expect($response->getContent())->not->toContain('NotFoundHttpException')
        ->not->toContain('vendor/laravel')
        ->not->toContain('Stack trace');
})->with(['tamu' => false, 'petugas' => true]);

test('ID yang tidak ada dan akses yang ditolak juga memakai halaman error ramah', function () {
    config(['app.debug' => false]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('bookings.show', 999999))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('error')->where('status', 404));

    $this->get(route('activity-log.index'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 403)
            ->where('title', 'Akses ditolak'));
});

test('error server tidak membocorkan pesan exception', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/_uji/error', fn () => throw new RuntimeException('SQLSTATE rahasia di server'));

    $response = $this->get('/_uji/error')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page->component('error')->where('status', 500));

    expect($response->getContent())->not->toContain('SQLSTATE rahasia');
});

test('saat APP_DEBUG=true detail error tetap untuk pengembang', function () {
    config(['app.debug' => true]);

    $this->get('/halaman-yang-tidak-ada')->assertNotFound()->assertDontSee('"component":"error"', false);
});

test('sesi kedaluwarsa (token CSRF) kembali ke halaman sebelumnya dengan pesan', function () {
    Route::middleware('web')->post('/_uji/token', fn () => throw new TokenMismatchException);

    $this->from('/login')
        ->post('/_uji/token')
        ->assertRedirect('/login')
        ->assertSessionHas('warning', 'Sesi Anda telah berakhir. Silakan ulangi tindakan tadi.');
});

/**
 * File sungguhan di disk agar MIME diperiksa dari isinya (finfo) seperti pada
 * request nyata; UploadedFile::fake() menebak MIME dari ekstensi nama file.
 */
function realUpload(string $name, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'unggah');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

test('file berbahaya yang menyamar sebagai foto ditolak validasi berdasarkan isinya', function (string $name, string $content) {
    Storage::fake('local');
    Storage::fake('public');
    $booking = Booking::factory()->scheduled()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('bookings.pickup', $booking), ['photo' => realUpload($name, $content), 'source' => 'upload'])
        ->assertSessionHasErrors('photo');

    $this->post(route('cars.photos.store', ['car' => Car::factory()->create(), 'position' => 1]), [
        'photo' => realUpload($name, $content),
    ])->assertSessionHasErrors('photo');

    expect($booking->refresh()->status->value)->toBe('scheduled')
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'skrip PHP berekstensi .jpg' => ['foto.jpg', '<?php echo "uji"; ?>'],
    'SVG berisi skrip berekstensi .png' => ['foto.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
    'HTML berekstensi .webp' => ['foto.webp', '<html><body onload="alert(1)"></body></html>'],
]);

test('file yang lolos pemeriksaan MIME tetapi bukan gambar ditolak saat diproses ulang', function () {
    Storage::fake('local');
    $booking = Booking::factory()->scheduled()->create();

    // fake() melaporkan image/jpeg karena ekstensinya; isi tetap skrip PHP.
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('bookings.pickup', $booking), [
            'photo' => UploadedFile::fake()->createWithContent('foto.jpg', '<?php system($_GET["c"]); ?>'),
            'source' => 'upload',
        ])
        ->assertInertiaFlash('toast.message', 'File tidak dapat dibaca sebagai gambar. Gunakan foto JPG, PNG, atau WebP.');

    expect($booking->refresh()->status->value)->toBe('scheduled')
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('field yang tidak diizinkan diabaikan saat mengubah profil (mass assignment)', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@kantor.test']);

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'penyusup@kantor.test',
            'role' => 'manager',
            'is_active' => false,
            'must_change_password' => true,
        ])
        ->assertSessionHasNoErrors();

    $admin->refresh();

    expect($admin->name)->toBe('Nama Baru')
        ->and($admin->email)->toBe('admin@kantor.test')
        ->and($admin->role->value)->toBe('admin')
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->must_change_password)->toBeFalse();
});
