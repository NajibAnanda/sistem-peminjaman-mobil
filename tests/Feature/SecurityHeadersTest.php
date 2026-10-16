<?php

use App\Models\User;

test('header keamanan dikirim di halaman login', function () {
    $response = $this->get(route('login'))->assertOk();

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($response->headers->get('Permissions-Policy'))->toContain('camera=(self)');

    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->not->toContain('bunny.net')
        ->not->toContain("script-src 'self' 'unsafe-inline'");
});

test('nonce CSP sama dengan nonce skrip inline di halaman', function () {
    $response = $this->get(route('login'));

    preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $matches);

    expect($matches[1] ?? null)->not->toBeNull();
    $response->assertSee('nonce="'.$matches[1].'"', escape: false);
});

test('header keamanan juga dikirim di halaman yang memerlukan login', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy');
});

test('header keamanan dikirim di halaman tidak ditemukan', function () {
    $this->get('/halaman-tidak-ada')
        ->assertNotFound()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});
