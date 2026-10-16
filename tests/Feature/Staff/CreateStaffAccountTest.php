<?php

use App\Enums\ActivityEvent;
use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia as Assert;

test('Pengelola dapat membuat akun dengan password sementara yang tampil sekali', function () {
    $manager = User::factory()->manager()->create();

    $response = $this->actingAs($manager)
        ->post(route('staff.store'), [
            'name' => 'Dewi Lestari',
            'email' => '  Dewi.Lestari@Kantor.TEST ',
            'role' => 'admin',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('staff.index'));

    $staff = User::query()->where('email', 'dewi.lestari@kantor.test')->sole();
    $flash = $response->baseResponse->getSession()->get(SessionKey::FLASH_DATA);
    $password = $flash['temporaryPassword']['password'];

    expect($staff->role)->toBe(StaffRole::Admin)
        ->and($staff->is_active)->toBeTrue()
        ->and($staff->must_change_password)->toBeTrue()
        ->and(Hash::check($password, $staff->password))->toBeTrue()
        ->and($password)->toHaveLength(12)->toMatch('/[A-Za-z]/')->toMatch('/\d/')
        ->and($flash['temporaryPassword']['email'])->toBe('dewi.lestari@kantor.test')
        ->and($flash['toast']['type'])->toBe('success');

    // Setelah redirect, password tetap tidak pernah dikirim sebagai prop halaman.
    $this->get(route('staff.index'))->assertInertia(fn (Assert $page) => $page
        ->where('staff.data', fn ($rows) => ! str_contains(json_encode($rows), $password)),
    );
});

test('pembuatan akun tercatat di log aktivitas tanpa password', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)->post(route('staff.store'), [
        'name' => 'Dewi Lestari',
        'email' => 'dewi@kantor.test',
        'role' => 'manager',
    ]);

    $activity = lastActivity();
    $staff = User::query()->where('email', 'dewi@kantor.test')->sole();

    expect($activity->log_name)->toBe('staff')
        ->and($activity->event)->toBe(ActivityEvent::Created->value)
        ->and($activity->causer_id)->toBe($manager->id)
        ->and($activity->subject_id)->toBe($staff->id)
        ->and($activity->attribute_changes['attributes'])->toMatchArray([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@kantor.test',
            'role' => 'manager',
        ])
        ->and(json_encode($activity->toArray()))->not->toContain('password":');
});

test('akun baru wajib mengganti password saat login pertama', function () {
    $this->actingAs(User::factory()->manager()->create())->post(route('staff.store'), [
        'name' => 'Dewi',
        'email' => 'dewi@kantor.test',
        'role' => 'admin',
    ]);

    $this->actingAs(User::query()->where('email', 'dewi@kantor.test')->sole())
        ->get(route('dashboard'))
        ->assertRedirect(route('password.change'));
});

test('validasi pembuatan akun', function (array $data, string $field, string $message) {
    User::factory()->create(['email' => 'terpakai@kantor.test']);

    $this->actingAs(User::factory()->manager()->create())
        ->post(route('staff.store'), [...['name' => 'Dewi', 'email' => 'baru@kantor.test', 'role' => 'admin'], ...$data])
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'nama kosong' => [['name' => ''], 'name', 'Nama wajib diisi.'],
    'email tidak valid' => [['email' => 'bukan-email'], 'email', 'Email harus berupa alamat email yang valid.'],
    'email duplikat beda huruf' => [['email' => 'TERPAKAI@kantor.test'], 'email', 'Email sudah dipakai.'],
    'peran tidak dikenal' => [['role' => 'superadmin'], 'role', 'Peran yang dipilih tidak valid.'],
]);

test('Admin tidak dapat membuat akun petugas', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('staff.store'), ['name' => 'X', 'email' => 'x@kantor.test', 'role' => 'manager'])
        ->assertForbidden();

    expect(User::query()->where('email', 'x@kantor.test')->exists())->toBeFalse();
});

test('tamu diarahkan ke login saat membuat akun', function () {
    $this->post(route('staff.store'), ['name' => 'X', 'email' => 'x@kantor.test', 'role' => 'admin'])
        ->assertRedirect(route('login'));
});
