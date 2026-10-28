<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Employee\CreateDivision;
use App\Actions\Employee\DeleteDivision;
use App\Http\Requests\Employee\DestroyDivisionRequest;
use App\Http\Requests\Employee\StoreDivisionRequest;
use Illuminate\Http\RedirectResponse;

final class DivisionController extends Controller
{
    public function store(StoreDivisionRequest $request, CreateDivision $action): RedirectResponse
    {
        $division = $action->handle($request->divisionName(), $request->user());

        return back()->with('success', "Divisi {$division->name} berhasil ditambahkan.");
    }

    public function destroy(DestroyDivisionRequest $request, string $division, DeleteDivision $action): RedirectResponse
    {
        $name = trim($division);

        $action->handle($name, $request->user());

        return back()->with('success', "Divisi {$name} berhasil dihapus.");
    }
}
