<?php

use App\Enums\ActivityEvent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

test('halaman profil dapat dibuka', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/profile'));
});

test('petugas dapat mengubah namanya dan perubahan tercatat', function () {
    $user = User::factory()->create(['name' => 'Budi']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Budi Santoso'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('Budi Santoso')
        ->and(lastActivity()->event)->toBe(ActivityEvent::Updated->value)
        ->and(lastActivity()->attribute_changes->toArray())->toBe([
            'old' => ['name' => 'Budi'],
            'attributes' => ['name' => 'Budi Santoso'],
        ]);
});

test('petugas tidak dapat mengubah emailnya sendiri lewat profil', function () {
    $user = User::factory()->create(['email' => 'asli@kantor.test']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'baru@kantor.test',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe('asli@kantor.test')
        ->and(Activity::query()->count())->toBe(0);
});
