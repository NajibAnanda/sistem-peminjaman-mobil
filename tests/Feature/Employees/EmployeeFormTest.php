<?php

use App\Enums\ActivityEvent;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\User;
use Inertia\Support\SessionKey;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
});

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function employeePayload(array $overrides = []): array
{
    return [...[
        'employee_number' => 'k2024001',
        'name' => ' Dewi Lestari ',
        'division' => 'Keuangan',
        'phone' => '+62 812-3456-7890',
    ], ...$overrides];
}

test('karyawan baru disimpan dengan NIP huruf besar dan no HP 08xx', function () {
    $this->actingAs($this->user)
        ->post(route('employees.store'), employeePayload())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $employee = Employee::query()->sole();

    expect($employee->employee_number)->toBe('K2024001')
        ->and($employee->name)->toBe('Dewi Lestari')
        ->and($employee->phone)->toBe('081234567890')
        ->and($employee->is_active)->toBeTrue()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Created->value);
});

test('validasi NIP, no HP, dan divisi', function (array $overrides, string $field, string $message) {
    Employee::factory()->create(['employee_number' => 'K2024001']);

    $this->actingAs($this->user)
        ->post(route('employees.store'), employeePayload(['employee_number' => 'K9', ...$overrides]))
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'NIP duplikat beda huruf' => [['employee_number' => 'k2024001'], 'employee_number', 'NIP sudah dipakai.'],
    'NIP berspasi' => [['employee_number' => 'K 2024'], 'employee_number', 'NIP hanya boleh berisi huruf dan angka, tanpa spasi atau simbol.'],
    'NIP bersimbol' => [['employee_number' => 'K-2024'], 'employee_number', 'NIP hanya boleh berisi huruf dan angka, tanpa spasi atau simbol.'],
    'NIP terlalu panjang' => [['employee_number' => str_repeat('A', 31)], 'employee_number', 'NIP tidak boleh lebih dari 30 karakter.'],
    'no HP 62 tanpa plus' => [['phone' => '6281234567890'], 'phone', 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.'],
    'no HP telepon rumah' => [['phone' => '0215551234'], 'phone', 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.'],
    'no HP terlalu pendek' => [['phone' => '081234567'], 'phone', 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.'],
    'no HP terlalu panjang' => [['phone' => '0812345678901234'], 'phone', 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.'],
    'divisi tidak dikenal' => [['division' => 'Pemasaran'], 'division', 'Divisi yang dipilih tidak valid.'],
]);

test('panjang no HP dihitung setelah normalisasi', function (string $phone) {
    $this->actingAs($this->user)
        ->post(route('employees.store'), employeePayload(['phone' => $phone]))
        ->assertSessionHasNoErrors();
})->with([
    '10 digit' => '0812345678',
    '15 digit' => '081234567890123',
    '+62 menjadi 10 digit' => '+62812345678',
]);

test('data karyawan dapat diubah dan perubahan tercatat', function () {
    $employee = Employee::factory()->create(['division' => 'Keuangan']);

    $this->actingAs($this->user)
        ->put(route('employees.update', $employee), [
            'employee_number' => $employee->employee_number,
            'name' => $employee->name,
            'division' => 'Teknik',
            'phone' => $employee->phone,
        ])
        ->assertSessionHasNoErrors();

    expect($employee->fresh()->division)->toBe('Teknik')
        ->and(lastActivity()->attribute_changes->toArray())->toBe([
            'old' => ['division' => 'Keuangan'],
            'attributes' => ['division' => 'Teknik'],
        ]);
});

test('karyawan dapat dinonaktifkan dan diaktifkan kembali', function () {
    $employee = Employee::factory()->create();
    Booking::factory()->completed()->for($employee)->create();

    $this->actingAs($this->user)->post(route('employees.deactivate', $employee))->assertSessionHas('success');
    expect($employee->fresh()->is_active)->toBeFalse()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Deactivated->value);

    $this->post(route('employees.activate', $employee))->assertSessionHas('success');
    expect($employee->fresh()->is_active)->toBeTrue();
});

test('karyawan dengan peminjaman berjalan tidak dapat dinonaktifkan (FR-10)', function (string $state) {
    $employee = Employee::factory()->create();
    $booking = Booking::factory()->{$state}()->for($employee)->create();

    $response = $this->actingAs($this->user)
        ->post(route('employees.deactivate', $employee))
        ->assertInertiaFlash('toast.type', 'error');

    expect($employee->fresh()->is_active)->toBeTrue()
        ->and($response->baseResponse->getSession()->get(SessionKey::FLASH_DATA)['toast']['message'])
        ->toContain($booking->code);
})->with(['dijadwalkan' => 'scheduled', 'aktif' => 'active']);

test('tamu tidak dapat mengubah data karyawan', function () {
    $employee = Employee::factory()->create();

    $this->post(route('employees.store'), employeePayload())->assertRedirect(route('login'));
    $this->post(route('employees.deactivate', $employee))->assertRedirect(route('login'));

    expect(Employee::query()->count())->toBe(1)
        ->and($employee->fresh()->is_active)->toBeTrue();
});
