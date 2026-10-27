<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->user = User::factory()->admin()->create();
});

test('petugas dapat menambahkan divisi baru', function () {
    $this->actingAs($this->user)
        ->from(route('employees.index'))
        ->post(route('divisions.store'), [
            'name' => 'Operasional Pelabuhan',
        ])
        ->assertRedirect(route('employees.index'))
        ->assertSessionHas('success', 'Divisi Operasional Pelabuhan berhasil ditambahkan.');

    $this->assertDatabaseHas('divisions', [
        'name' => 'Operasional Pelabuhan',
    ]);
});

test('nama divisi duplikat ditolak', function () {
    Division::create(['name' => 'Operasional Pelabuhan']);

    $this->actingAs($this->user)
        ->post(route('divisions.store'), [
            'name' => 'Operasional Pelabuhan',
        ])
        ->assertSessionHasErrors(['name']);
});

test('nama divisi wajib diisi dan maksimal 50 karakter', function () {
    $this->actingAs($this->user)
        ->post(route('divisions.store'), [
            'name' => '',
        ])
        ->assertSessionHasErrors(['name']);

    $this->actingAs($this->user)
        ->post(route('divisions.store'), [
            'name' => str_repeat('a', 51),
        ])
        ->assertSessionHasErrors(['name']);
});

test('petugas dapat menghapus divisi yang tidak memiliki pegawai', function () {
    Division::create(['name' => 'Divisi Sementara']);

    $this->actingAs($this->user)
        ->from(route('employees.index'))
        ->delete(route('divisions.destroy', 'Divisi Sementara'))
        ->assertRedirect(route('employees.index'))
        ->assertSessionHas('success', 'Divisi Divisi Sementara berhasil dihapus.');

    $this->assertDatabaseMissing('divisions', [
        'name' => 'Divisi Sementara',
    ]);
});

test('divisi yang masih memiliki pegawai tidak dapat dihapus', function () {
    Division::firstOrCreate(['name' => 'Keuangan']);
    Employee::factory()->create(['division' => 'Keuangan']);

    $this->actingAs($this->user)
        ->from(route('employees.index'))
        ->delete(route('divisions.destroy', 'Keuangan'))
        ->assertRedirect(route('employees.index'))
        ->assertSessionHas('error', 'Divisi Keuangan tidak dapat dihapus karena masih digunakan oleh 1 pegawai.');

    $this->assertDatabaseHas('divisions', [
        'name' => 'Keuangan',
    ]);
});

test('tamu tidak dapat menambah atau menghapus divisi', function () {
    $this->post(route('divisions.store'), ['name' => 'Rahasia'])
        ->assertRedirect(route('login'));

    $this->delete(route('divisions.destroy', 'Rahasia'))
        ->assertRedirect(route('login'));
});

test('divisi yang dihapus tidak muncul lagi di halaman daftar pegawai', function () {
    Division::create(['name' => 'Divisi Dihapus']);

    $this->actingAs($this->user)
        ->delete(route('divisions.destroy', 'Divisi Dihapus'))
        ->assertRedirect();

    $this->actingAs($this->user)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('divisions', fn ($divisions) => ! collect($divisions)->contains('value', 'Divisi Dihapus'))
        );
});
