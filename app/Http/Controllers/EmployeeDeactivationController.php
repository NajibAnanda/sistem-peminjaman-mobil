<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Employee\DeactivateEmployee;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmployeeDeactivationController extends Controller
{
    public function __invoke(Request $request, Employee $employee, DeactivateEmployee $action): RedirectResponse
    {
        $employee = $action->handle($employee, $request->user());

        return back()->with('success', "Pegawai {$employee->name} dinonaktifkan.");
    }
}
