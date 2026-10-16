<?php

use App\Enums\CarDisplayStatus;
use App\Enums\CarOperationalStatus;

test('status tampilan mobil mengikuti urutan prioritas PRD 5.3', function (bool $isActive, CarOperationalStatus $operational, bool $hasActiveBooking, CarDisplayStatus $expected) {
    expect(CarDisplayStatus::resolve($isActive, $operational, $hasActiveBooking))->toBe($expected);
})->with([
    'nonaktif mengalahkan semua' => [false, CarOperationalStatus::Maintenance, true, CarDisplayStatus::Inactive],
    'maintenance mengalahkan dipinjam' => [true, CarOperationalStatus::Maintenance, true, CarDisplayStatus::Maintenance],
    'dipinjam' => [true, CarOperationalStatus::Operational, true, CarDisplayStatus::Borrowed],
    'tersedia' => [true, CarOperationalStatus::Operational, false, CarDisplayStatus::Available],
]);
