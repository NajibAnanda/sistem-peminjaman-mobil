<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('flash message Laravel ditampilkan sebagai toast', function (string $type) {
    $this->actingAs(User::factory()->create())
        ->withSession([$type => 'Data berhasil disimpan.'])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toast', ['type' => $type, 'message' => 'Data berhasil disimpan.']),
        );
})->with(['success', 'info', 'warning', 'error']);

test('tanpa flash message tidak ada toast', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->missingFlash('toast'));
});
