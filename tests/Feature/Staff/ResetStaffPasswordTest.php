<?php

use App\Enums\ActivityEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Support\SessionKey;

test('Pengelola dapat mereset password petugas menjadi password sementara', function () {
    config(['session.driver' => 'database']);
    $manager = User::factory()->manager()->create();
    $staff = User::factory()->create();
    DB::table('sessions')->insert([
        'id' => 'sesi-lama',
        'user_id' => $staff->id,
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);

    $response = $this->actingAs($manager)->post(route('staff.reset-password', $staff));

    $password = $response->baseResponse->getSession()->get(SessionKey::FLASH_DATA)['temporaryPassword']['password'];
    $staff->refresh();

    expect(Hash::check($password, $staff->password))->toBeTrue()
        ->and(Hash::check('password', $staff->password))->toBeFalse()
        ->and($staff->must_change_password)->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $staff->id)->exists())->toBeFalse();
});

test('reset password tercatat tanpa menyimpan password', function () {
    $manager = User::factory()->manager()->create();
    $staff = User::factory()->create();

    $response = $this->actingAs($manager)->post(route('staff.reset-password', $staff));
    $password = $response->baseResponse->getSession()->get(SessionKey::FLASH_DATA)['temporaryPassword']['password'];

    $activity = lastActivity();

    expect($activity->event)->toBe(ActivityEvent::PasswordReset->value)
        ->and($activity->causer_id)->toBe($manager->id)
        ->and($activity->subject_id)->toBe($staff->id)
        ->and(json_encode($activity->toArray()))->not->toContain($password);
});

test('Pengelola tidak dapat mereset password dirinya sendiri lewat Kelola Petugas', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->post(route('staff.reset-password', $manager))
        ->assertInertiaFlash('toast.type', 'error');

    expect(Hash::check('password', $manager->fresh()->password))->toBeTrue();
});

test('Admin tidak dapat mereset password petugas', function () {
    $staff = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('staff.reset-password', $staff))
        ->assertForbidden();

    expect(Hash::check('password', $staff->fresh()->password))->toBeTrue();
});
