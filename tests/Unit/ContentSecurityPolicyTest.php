<?php

use App\Support\ContentSecurityPolicy;

/**
 * Regresi: saat `composer run dev`, Vite berjalan di alamat IPv6
 * (http://[::1]:5173). Browser menolak alamat IPv6 sebagai sumber CSP, sehingga
 * CSS dan koneksi HMR diblokir dan halaman tampil tanpa style.
 */
test('server Vite di alamat IPv6 ditulis dalam bentuk sumber CSP yang sah', function () {
    $csp = ContentSecurityPolicy::build('abc', 'http://[::1]:5173');

    expect($csp)
        ->not->toContain('[')
        ->toContain("script-src 'self' 'nonce-abc' http://*:5173;")
        ->toContain("style-src 'self' 'unsafe-inline' http://*:5173;")
        ->toContain("connect-src 'self' http://*:5173 ws://*:5173;");
});

test('server Vite di alamat biasa tetap memakai alamatnya sendiri', function () {
    $csp = ContentSecurityPolicy::build('abc', 'http://127.0.0.1:5173');

    expect($csp)
        ->toContain("style-src 'self' 'unsafe-inline' http://127.0.0.1:5173;")
        ->toContain("connect-src 'self' http://127.0.0.1:5173 ws://127.0.0.1:5173;");
});

test('server Vite lewat HTTPS memakai websocket aman', function () {
    expect(ContentSecurityPolicy::build('abc', 'https://sistem-peminjaman-mobil.test:5173'))
        ->toContain('wss://sistem-peminjaman-mobil.test:5173');
});

test('tanpa server Vite, CSP hanya mengizinkan origin sendiri', function () {
    $csp = ContentSecurityPolicy::build('abc');

    expect($csp)
        ->toBe("default-src 'self'; script-src 'self' 'nonce-abc'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
});

test('alamat server Vite yang tidak terbaca diabaikan', function () {
    expect(ContentSecurityPolicy::build('abc', 'bukan-url'))->toBe(ContentSecurityPolicy::build('abc'));
});
