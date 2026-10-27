<?php

use App\Enums\ActivityEvent;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\User;
use Inertia\Support\SessionKey;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->user = User::factory()->admin()->create();
});

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function employeePayload(array $overrides = []): array
{
    return [...[
        'name' => ' Dewi Lestari ',
        'division' => 'Keuangan',
        'phone' => '+62 812-3456-7890',
    ], ...$overrides];
}

test('pegawai baru disimpan dengan no HP 08xx', function () {
    $this->actingAs($this->user)
        ->post(route('employees.store'), employeePayload())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $employee = Employee::query()->sole();

    expect($employee->name)->toBe('Dewi Lestari')
        ->and($employee->phone)->toBe('081234567890')
        ->and($employee->is_active)->toBeTrue()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Created->value);
});

test('validasi no HP dan divisi', function (array $overrides, string $field, string $message) {
    Employee::factory()->create(['phone' => '081234567890']);

    $this->actingAs($this->user)
        ->post(route('employees.store'), employeePayload($overrides))
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'no HP duplikat' => [['phone' => '081234567890'], 'phone', 'No HP sudah dipakai pegawai lain.'],
    'no HP 62 tanpa plus' => [['phone' => '6281234567890'], 'phone', 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.'],
    'no HP telepon rumah' => [['phone' => '0215551234'], 'phone', 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.'],
    'no HP terlalu pendek' => [['phone' => '081234567'], 'phone', 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.'],
    'no HP terlalu panjang' => [['phone' => '0812345678901234'], 'phone', 'No HP harus diawali 08 atau +62 dan berisi 10–15 digit.'],
    'divisi terlalu panjang' => [['division' => str_repeat('A', 51)], 'division', 'Divisi tidak boleh lebih dari 50 karakter.'],
]);

test('pegawai dengan divisi baru kustom berhasil disimpan', function () {
    $this->actingAs($this->user)
        ->post(route('employees.store'), employeePayload(['division' => 'Operasional Pelabuhan']))
        ->assertSessionHasNoErrors();

    expect(Employee::query()->where('division', 'Operasional Pelabuhan')->exists())->toBeTrue();
});

test('panjang no HP dihitung setelah normalisasi', function (string $phone) {
    $this->actingAs($this->user)
        ->post(route('employees.store'), employeePayload(['phone' => $phone]))
        ->assertSessionHasNoErrors();
})->with([
    '10 digit' => '0812345678',
    '15 digit' => '081234567890123',
    '+62 menjadi 10 digit' => '+62812345678',
]);

test('data pegawai dapat diubah dan perubahan tercatat', function () {
    $employee = Employee::factory()->create(['division' => 'Keuangan']);

    $this->actingAs($this->user)
        ->put(route('employees.update', $employee), [
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

test('pegawai dapat dinonaktifkan dan diaktifkan kembali', function () {
    $employee = Employee::factory()->create();
    Booking::factory()->completed()->for($employee)->create();

    $this->actingAs($this->user)->post(route('employees.deactivate', $employee))->assertSessionHas('success');
    expect($employee->fresh()->is_active)->toBeFalse()
        ->and(lastActivity()->event)->toBe(ActivityEvent::Deactivated->value);

    $this->post(route('employees.activate', $employee))->assertSessionHas('success');
    expect($employee->fresh()->is_active)->toBeTrue();
});

test('pegawai dengan peminjaman berjalan tidak dapat dinonaktifkan (FR-10)', function (string $state) {
    $employee = Employee::factory()->create();
    $booking = Booking::factory()->{$state}()->for($employee)->create();

    $response = $this->actingAs($this->user)
        ->post(route('employees.deactivate', $employee))
        ->assertInertiaFlash('toast.type', 'error');

    expect($employee->fresh()->is_active)->toBeTrue()
        ->and($response->baseResponse->getSession()->get(SessionKey::FLASH_DATA)['toast']['message'])
        ->toContain($booking->code);
})->with(['dijadwalkan' => 'scheduled', 'aktif' => 'active']);

test('tamu tidak dapat mengubah data pegawai', function () {
    $employee = Employee::factory()->create();

    $this->post(route('employees.store'), employeePayload())->assertRedirect(route('login'));
    $this->post(route('employees.deactivate', $employee))->assertRedirect(route('login'));

    expect(Employee::query()->count())->toBe(1)
        ->and($employee->fresh()->is_active)->toBeTrue();
});
