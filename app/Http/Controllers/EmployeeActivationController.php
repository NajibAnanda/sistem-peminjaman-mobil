<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Employee\ActivateEmployee;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmployeeActivationController extends Controller
{
    public function __invoke(Request $request, Employee $employee, ActivateEmployee $action): RedirectResponse
    {
        $employee = $action->handle($employee, $request->user());

        return back()->with('success', "Karyawan {$employee->name} diaktifkan kembali.");
    }
}
