<?php

use App\Models\User;
use Illuminate\Validation\Rules\Password;

test('password baru harus minimal 10 karakter berisi huruf dan angka', function (string $password, ?string $message) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

    if ($message === null) {
        $response->assertSessionHasNoErrors();
    } else {
        $response->assertSessionHasErrors(['password' => $message]);
    }
})->with([
    'valid' => ['GarasiKantor7', null],
    'tepat 10 karakter' => ['abcdefghi1', null],
    'terlalu pendek' => ['abcdefg12', 'Password minimal 10 karakter.'],
    'tanpa angka' => ['abcdefghijk', 'Password harus berisi setidaknya satu angka.'],
    'tanpa huruf' => ['12345678901', 'Password harus berisi setidaknya satu huruf.'],
]);

test('cek password bocor nonaktif secara default', function () {
    expect(Password::default()->appliedRules())
        ->min->toBe(10)
        ->letters->toBeTrue()
        ->numbers->toBeTrue()
        ->uncompromised->toBeFalse();
});

test('cek password bocor aktif bila diizinkan lewat konfigurasi', function () {
    config(['fleet.security.password_check_uncompromised' => true]);

    expect(Password::default()->appliedRules()['uncompromised'])->toBeTrue();
});
