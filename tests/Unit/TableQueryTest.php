<?php

use App\Support\TableQuery;
use Illuminate\Http\Request;

const ALLOWED = ['status' => ['active', 'inactive'], 'role' => ['manager', 'admin']];

test('membaca pencarian dan filter yang diizinkan', function () {
    $table = TableQuery::fromRequest(
        Request::create('/staff', 'GET', ['search' => '  budi  ', 'status' => ['inactive'], 'role' => ['admin', 'manager']]),
        ALLOWED,
    );

    expect($table->search)->toBe('budi')
        ->and($table->filter('status'))->toBe(['inactive'])
        ->and($table->filter('role'))->toBe(['admin', 'manager'])
        ->and($table->toArray())->toBe([
            'search' => 'budi',
            'filters' => ['status' => ['inactive'], 'role' => ['admin', 'manager']],
        ]);
});

test('nilai tidak dikenal, duplikat, dan tipe salah diabaikan', function () {
    $table = TableQuery::fromRequest(
        Request::create('/staff', 'GET', [
            'search' => ['bukan', 'teks'],
            'status' => ['active', 'active', 'hapus', 1],
            'role' => 'superadmin',
        ]),
        ALLOWED,
    );

    expect($table->search)->toBeNull()
        ->and($table->filter('status'))->toBe(['active'])
        ->and($table->filter('role'))->toBe([])
        ->and($table->filter('tidak-ada'))->toBe([]);
});

test('pencarian kosong menjadi null dan pencarian panjang dipotong', function () {
    expect(TableQuery::fromRequest(Request::create('/', 'GET', ['search' => '   ']), [])->search)->toBeNull()
        ->and(mb_strlen((string) TableQuery::fromRequest(Request::create('/', 'GET', ['search' => str_repeat('a', 500)]), [])->search))->toBe(100);
});
