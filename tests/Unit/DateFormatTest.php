<?php

use App\Support\DateFormat;

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
