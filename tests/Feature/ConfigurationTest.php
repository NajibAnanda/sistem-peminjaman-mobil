<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

test('zona waktu dan bahasa aplikasi sesuai PRD', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(date_default_timezone_get())->toBe('Asia/Jakarta')
        ->and(app()->getLocale())->toBe('id');
});

test('disk privat tidak disajikan lewat route storage bawaan', function () {
    expect(config('filesystems.disks.local.serve'))->toBeFalse();
});

test('pesan validasi memakai Bahasa Indonesia dan nama field Indonesia', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->post(route('staff.store'), ['name' => '', 'email' => 'bukan-email', 'role' => 'admin'])
        ->assertSessionHasErrors([
            'name' => 'Nama wajib diisi.',
            'email' => 'Email harus berupa alamat email yang valid.',
        ]);
});

test('lazy loading dicegah di luar production', function () {
    expect(Model::preventsLazyLoading())->toBeTrue();
});
