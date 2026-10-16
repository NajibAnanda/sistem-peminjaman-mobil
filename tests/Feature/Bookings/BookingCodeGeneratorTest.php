<?php

use App\Support\BookingCodeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

test('kode berurutan per bulan dan mulai ulang di bulan baru', function () {
    $codes = app(BookingCodeGenerator::class);
    $october = CarbonImmutable::parse('2026-10-31 23:59');
    $november = CarbonImmutable::parse('2026-11-01 00:00');

    $result = DB::transaction(fn () => [
        $codes->next($october),
        $codes->next($october),
        $codes->next($november),
        $codes->next($october),
    ]);

    expect($result)->toBe(['PMJ-202610-0001', 'PMJ-202610-0002', 'PMJ-202611-0001', 'PMJ-202610-0003']);
});

test('pembuat kode menolak dipanggil di luar transaksi', function () {
    // RefreshDatabase membungkus test dalam transaksi; keluar sementara untuk
    // meniru pemanggilan tanpa transaksi.
    DB::rollBack();

    try {
        expect(fn () => app(BookingCodeGenerator::class)->next())->toThrow(LogicException::class);
    } finally {
        DB::beginTransaction();
    }
});
