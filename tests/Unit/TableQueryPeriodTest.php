<?php

use App\Support\TableQuery;
use Illuminate\Http\Request;
use Tests\TestCase;

// Periode dibaca dalam zona waktu aplikasi (config), jadi butuh aplikasi.
uses(TestCase::class);

test('periode hanya dibaca bila diaktifkan dan dikirim balik ke frontend', function () {
    $request = Request::create('/history', 'GET', ['from' => '2026-10-01', 'to' => '2026-10-31']);

    $without = TableQuery::fromRequest($request, []);
    $with = TableQuery::fromRequest($request, [], withPeriod: true);

    expect($without->from)->toBeNull()
        ->and($without->toArray())->not->toHaveKey('period')
        ->and($with->from?->format('Y-m-d H:i:s'))->toBe('2026-10-01 00:00:00')
        ->and($with->to?->format('Y-m-d H:i:s'))->toBe('2026-10-31 23:59:59')
        ->and($with->toArray()['period'])->toBe(['from' => '2026-10-01', 'to' => '2026-10-31']);
});

test('periode tidak valid diabaikan dan rentang terbalik ditukar', function (array $query, ?string $from, ?string $to) {
    $table = TableQuery::fromRequest(Request::create('/', 'GET', $query), [], withPeriod: true);

    expect($table->toArray()['period'])->toBe(['from' => $from, 'to' => $to]);
})->with([
    'tanggal tidak ada' => [['from' => '2026-02-31', 'to' => '2026-13-01'], null, null],
    'bukan tanggal' => [['from' => 'kemarin', 'to' => ['2026-10-01']], null, null],
    'terbalik' => [['from' => '2026-10-31', 'to' => '2026-10-01'], '2026-10-01', '2026-10-31'],
    'hanya sampai' => [['to' => '2026-10-05'], null, '2026-10-05'],
]);
