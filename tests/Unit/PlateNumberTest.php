<?php

use App\Support\PlateNumber;

test('plat nomor dinormalisasi ke satu bentuk baku', function (string $input, string $expected) {
    expect(PlateNumber::normalize($input))->toBe($expected);
})->with([
    'sudah baku' => ['B 1234 ABC', 'B 1234 ABC'],
    'huruf kecil rapat' => ['b1234abc', 'B 1234 ABC'],
    'spasi ganda' => ['  AB   12   CD ', 'AB 12 CD'],
    'tanda hubung' => ['D-5678-XY', 'D 5678 XY'],
    'tanpa akhiran' => ['ri1', 'RI 1'],
]);
