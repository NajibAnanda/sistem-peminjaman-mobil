<?php

use App\Actions\Staff\UpdateStaffAccount;
use App\Enums\ActivityEvent;
use App\Enums\StaffRole;
use App\Exceptions\StaffAccountException;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('Pengelola dapat mengubah nama, email, dan peran petugas', function () {
    $manager = User::factory()->manager()->create();
    $staff = User::factory()->admin()->create(['name' => 'Budi', 'email' => 'budi@kantor.test']);

    $this->actingAs($manager)
        ->put(route('staff.update', $staff), [
            'name' => 'Budi Santoso',
            'email' => 'Budi.Santoso@Kantor.test',
            'role' => 'manager',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Akun petugas berhasil diperbarui.');

    $staff->refresh();

    expect($staff->name)->toBe('Budi Santoso')
        ->and($staff->email)->toBe('budi.santoso@kantor.test')
        ->and($staff->role)->toBe(StaffRole::Manager);
});

test('perubahan akun mencatat nilai sebelum dan sesudah', function () {
    $manager = User::factory()->manager()->create();
    $staff = User::factory()->admin()->create(['name' => 'Budi', 'email' => 'budi@kantor.test']);

    $this->actingAs($manager)->put(route('staff.update', $staff), [
        'name' => 'Budi Santoso',
        'email' => 'budi@kantor.test',
        'role' => 'admin',
    ]);

    $activity = lastActivity();

    expect($activity->event)->toBe(ActivityEvent::Updated->value)
        ->and($activity->causer_id)->toBe($manager->id)
        ->and($activity->attribute_changes->toArray())->toBe([
            'old' => ['name' => 'Budi'],
            'attributes' => ['name' => 'Budi Santoso'],
        ]);
});

test('menyimpan tanpa perubahan tidak membuat log', function () {
    $manager = User::factory()->manager()->create();
    $staff = User::factory()->admin()->create();

    $this->actingAs($manager)->put(route('staff.update', $staff), [
        'name' => $staff->name,
        'email' => $staff->email,
        'role' => 'admin',
    ]);

    expect(Activity::query()->count())->toBe(0);
});

test('Pengelola tidak dapat mengubah perannya sendiri', function () {
    $manager = User::factory()->manager()->create();
    User::factory()->manager()->create();

    $this->actingAs($manager)
        ->put(route('staff.update', $manager), [
            'name' => $manager->name,
            'email' => $manager->email,
            'role' => 'admin',
        ])
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => StaffAccountException::cannotChangeOwnRole()->getMessage()]);

    expect($manager->fresh()->role)->toBe(StaffRole::Manager);
});

test('Pengelola dapat mengubah nama dan emailnya sendiri', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->put(route('staff.update', $manager), [
            'name' => 'Nama Baru',
            'email' => 'baru@kantor.test',
            'role' => 'manager',
        ])
        ->assertSessionHasNoErrors();

    expect($manager->fresh()->email)->toBe('baru@kantor.test');
});

test('menurunkan peran Pengelola aktif terakhir ditolak', function () {
    // Lewat HTTP, pelaku selalu Pengelola aktif sehingga kasus ini hanya bisa
    // terjadi saat perubahan bersamaan; aturannya diuji langsung di Action.
    $lastManager = User::factory()->manager()->create();
    $actor = User::factory()->manager()->inactive()->create();

    app(UpdateStaffAccount::class)->handle($lastManager, [
        'name' => $lastManager->name,
        'email' => $lastManager->email,
        'role' => StaffRole::Admin,
    ], $actor);
})->throws(StaffAccountException::class, 'Minimal harus ada satu akun Pengelola aktif');

test('email yang dipakai akun lain ditolak, email sendiri boleh', function () {
    $manager = User::factory()->manager()->create();
    User::factory()->create(['email' => 'terpakai@kantor.test']);
    $staff = User::factory()->create(['email' => 'budi@kantor.test']);

    $this->actingAs($manager)
        ->put(route('staff.update', $staff), ['name' => 'Budi', 'email' => 'terpakai@kantor.test', 'role' => 'admin'])
        ->assertSessionHasErrors(['email' => 'Email sudah dipakai.']);

    $this->actingAs($manager)
        ->put(route('staff.update', $staff), ['name' => 'Budi', 'email' => 'budi@kantor.test', 'role' => 'admin'])
        ->assertSessionHasNoErrors();
});

test('Admin tidak dapat mengubah akun petugas', function () {
    $staff = User::factory()->create(['name' => 'Asli']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('staff.update', $staff), ['name' => 'Diubah', 'email' => $staff->email, 'role' => 'manager'])
        ->assertForbidden();

    expect($staff->fresh()->name)->toBe('Asli');
});
