<?php

use App\Actions\Staff\DeactivateStaffAccount;
use App\Enums\ActivityEvent;
use App\Exceptions\StaffAccountException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function insertSessionFor(User $user): void
{
    DB::table('sessions')->insert([
        'id' => 'sesi-'.$user->id,
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);
}

test('Pengelola dapat menonaktifkan akun dan semua sesinya berakhir', function () {
    config(['session.driver' => 'database']);
    $manager = User::factory()->manager()->create();
    $staff = User::factory()->create(['remember_token' => 'token-lama']);
    insertSessionFor($staff);

    $this->actingAs($manager)
        ->post(route('staff.deactivate', $staff))
        ->assertSessionHas('success');

    $staff->refresh();

    expect($staff->is_active)->toBeFalse()
        ->and($staff->remember_token)->not->toBe('token-lama')
        ->and(DB::table('sessions')->where('user_id', $staff->id)->exists())->toBeFalse()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Deactivated->value)
        ->and(lastActivity()->causer_id)->toBe($manager->id);
});

test('Pengelola tidak dapat menonaktifkan dirinya sendiri', function () {
    $manager = User::factory()->manager()->create();
    User::factory()->manager()->create();

    $this->actingAs($manager)
        ->post(route('staff.deactivate', $manager))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);

    expect($manager->fresh()->is_active)->toBeTrue();
});

test('Pengelola lain dapat dinonaktifkan selama masih ada Pengelola aktif', function () {
    $manager = User::factory()->manager()->create();
    $otherManager = User::factory()->manager()->create();

    $this->actingAs($manager)->post(route('staff.deactivate', $otherManager));

    expect($otherManager->fresh()->is_active)->toBeFalse();
});

test('Pengelola aktif terakhir tidak dapat dinonaktifkan', function () {
    // Lewat HTTP pelaku selalu Pengelola aktif lain; aturan ini menjaga kasus
    // perubahan bersamaan, sehingga diuji langsung di Action.
    $lastManager = User::factory()->manager()->create();
    $actor = User::factory()->manager()->inactive()->create();

    app(DeactivateStaffAccount::class)->handle($lastManager, $actor);
})->throws(StaffAccountException::class, 'Minimal harus ada satu akun Pengelola aktif');

test('menonaktifkan akun yang sudah nonaktif ditolak', function () {
    $staff = User::factory()->inactive()->create();

    $this->actingAs(User::factory()->manager()->create())
        ->post(route('staff.deactivate', $staff))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Akun ini sudah nonaktif.']);
});

test('Pengelola dapat mengaktifkan kembali akun', function () {
    $manager = User::factory()->manager()->create();
    $staff = User::factory()->inactive()->create();

    $this->actingAs($manager)->post(route('staff.activate', $staff));

    expect($staff->fresh()->is_active)->toBeTrue()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Activated->value);
});

test('mengaktifkan akun yang sudah aktif ditolak', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->post(route('staff.activate', User::factory()->create()))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Akun ini sudah aktif.']);
});

test('Admin tidak dapat menonaktifkan atau mengaktifkan akun', function (string $routeName, bool $active) {
    $staff = User::factory()->create(['is_active' => $active]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route($routeName, $staff))
        ->assertForbidden();

    expect($staff->fresh()->is_active)->toBe($active);
})->with([
    'nonaktifkan' => ['staff.deactivate', true],
    'aktifkan' => ['staff.activate', false],
]);
