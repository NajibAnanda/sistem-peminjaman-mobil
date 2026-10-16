<?php

use App\Enums\StaffRole;

test('peran petugas memiliki label Bahasa Indonesia', function (StaffRole $role, string $label) {
    expect($role->label())->toBe($label);
})->with([
    [StaffRole::Manager, 'Pengelola'],
    [StaffRole::Admin, 'Admin'],
]);

test('nilai peran yang disimpan tetap bahasa Inggris', function () {
    expect(array_map(fn (StaffRole $role) => $role->value, StaffRole::cases()))
        ->toBe(['manager', 'admin']);
});
