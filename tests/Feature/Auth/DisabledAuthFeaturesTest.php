<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('route registrasi, lupa password, verifikasi email, passkey, dan storage tidak tersedia', function (string $method, string $uri) {
    $this->call($method, $uri)->assertNotFound();
})->with([
    'registrasi' => ['GET', '/register'],
    'simpan registrasi' => ['POST', '/register'],
    'lupa password' => ['GET', '/forgot-password'],
    'kirim tautan reset' => ['POST', '/forgot-password'],
    'form reset password' => ['GET', '/reset-password/token-palsu'],
    'verifikasi email' => ['GET', '/email/verify'],
    'opsi login passkey' => ['GET', '/passkeys/login/options'],
    'opsi daftar passkey' => ['GET', '/user/passkeys/options'],
    'endpoint passkey' => ['GET', '/.well-known/passkey-endpoints'],
    'storage privat' => ['GET', '/storage/bookings/foto.jpg'],
]);

test('petugas tidak dapat menghapus akunnya sendiri', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertMethodNotAllowed();

    expect($user->fresh())->not->toBeNull();
});

test('tabel passkeys sudah dihapus', function () {
    expect(Schema::hasTable('passkeys'))->toBeFalse();
});
