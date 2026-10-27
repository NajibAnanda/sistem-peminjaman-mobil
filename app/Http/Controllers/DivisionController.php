<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DivisionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique(Division::class, 'name')],
        ], [
            'name.required' => 'Nama divisi wajib diisi.',
            'name.max' => 'Nama divisi tidak boleh lebih dari 50 karakter.',
            'name.unique' => 'Divisi ini sudah ada.',
        ]);

        $name = trim($validated['name']);
        $division = Division::create(['name' => $name]);

        activity()
            ->causedBy($request->user())
            ->performedOn($division)
            ->log("Menambahkan divisi: {$name}");

        return back()->with('success', "Divisi {$name} berhasil ditambahkan.");
    }

    public function destroy(Request $request, string $division): RedirectResponse
    {
        $name = trim(urldecode($division));

        $employeeCount = Employee::query()->where('division', $name)->count();

        if ($employeeCount > 0) {
            return back()->with('error', "Divisi {$name} tidak dapat dihapus karena masih digunakan oleh {$employeeCount} pegawai.");
        }

        Division::query()->where('name', $name)->delete();

        activity()
            ->causedBy($request->user())
            ->log("Menghapus divisi: {$name}");

        return back()->with('success', "Divisi {$name} berhasil dihapus.");
    }
}
