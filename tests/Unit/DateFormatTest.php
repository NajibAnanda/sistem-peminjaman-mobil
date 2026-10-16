<?php

use App\Support\DateFormat;
use Carbon\CarbonImmutable;
use Tests\TestCase;

// Format tanggal membaca zona waktu aplikasi (config), jadi butuh aplikasi.
uses(TestCase::class);

test('lama waktu ditulis dalam hari, jam, dan menit', function (int $minutes, string $expected) {
    expect(DateFormat::duration($minutes))->toBe($expected);
})->with([
    [0, '1 menit'],
    [45, '45 menit'],
    [60, '1 jam'],
    [135, '2 jam 15 menit'],
    [1440, '1 hari'],
    [1650, '1 hari 3 jam'],
    [4350, '3 hari'],
]);

test('tanggal & jam ditulis dalam zona waktu aplikasi dengan singkatan bulan yang sama dengan browser', function () {
    $utc = CarbonImmutable::parse('2026-08-05 17:30:00', 'UTC');

    // 17.30 UTC = 00.30 WIB keesokan harinya; "Agu" sama dengan Intl id-ID.
    expect(DateFormat::dateTime($utc))->toBe('6 Agu 2026, 00.30')
        ->and(DateFormat::dateTime(CarbonImmutable::parse('2026-10-16 14:30:00', 'Asia/Jakarta')))->toBe('16 Okt 2026, 14.30');
});
