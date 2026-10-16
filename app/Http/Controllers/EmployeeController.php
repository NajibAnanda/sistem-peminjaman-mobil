<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Employee\CreateEmployee;
use App\Actions\Employee\UpdateEmployee;
use App\Enums\ActiveStatus;
use App\Http\Controllers\Concerns\PaginatesForTable;
use App\Http\Requests\Employee\EmployeeIndexRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class EmployeeController extends Controller
{
    use PaginatesForTable;

    public function index(EmployeeIndexRequest $request): Response
    {
        $table = $request->tableQuery();

        $employees = $this->paginateForTable(
            Employee::query()->forTable($table)->orderByDesc('is_active')->orderBy('name'),
        );

        /** @var list<string> $divisions */
        $divisions = config('fleet.divisions');

        return Inertia::render('employees/index', [
            'employees' => EmployeeResource::collection($employees),
            'query' => $table->toArray(),
            'divisions' => array_map(fn (string $division): array => ['value' => $division, 'label' => $division], $divisions),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function store(StoreEmployeeRequest $request, CreateEmployee $action): RedirectResponse
    {
        $employee = $action->handle($request->employeeData(), $request->user());

        return back()->with('success', "Karyawan {$employee->name} berhasil ditambahkan.");
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, UpdateEmployee $action): RedirectResponse
    {
        $action->handle($employee, $request->employeeData(), $request->user());

        return back()->with('success', 'Data karyawan berhasil diperbarui.');
    }
}
