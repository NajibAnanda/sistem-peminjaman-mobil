<?php

use App\Support\PhoneNumber;

test('no HP dinormalisasi ke format 08xx', function (string $input, ?string $expected) {
    expect(PhoneNumber::normalize($input))->toBe($expected);
})->with([
    'sudah 08xx' => ['081234567890', '081234567890'],
    '+62 tanpa pemisah' => ['+6281234567890', '081234567890'],
    '+62 dengan spasi & tanda hubung' => ['+62 812-3456-7890', '081234567890'],
    '08xx dengan titik' => ['0812.3456.7890', '081234567890'],
    '62 tanpa plus' => ['6281234567890', null],
    'telepon rumah' => ['0215551234', null],
    'berisi huruf' => ['0812abc4567', null],
    'kosong' => ['', null],
]);
