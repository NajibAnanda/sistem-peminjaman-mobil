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
use App\Models\Division;
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

        /** @var array<string, int> $employeeCounts */
        $employeeCounts = Employee::query()
            ->selectRaw('division, count(*) as count')
            ->groupBy('division')
            ->pluck('count', 'division')
            ->map(fn ($c): int => (int) $c)
            ->all();

        /** @var list<string> $divisions */
        $divisions = array_values(array_unique(array_filter(array_merge(
            Division::query()->pluck('name')->all(),
            array_keys($employeeCounts),
        ))));
        sort($divisions);

        $divisionOptions = array_map(function (string $division) use ($employeeCounts): array {
            $count = $employeeCounts[$division] ?? 0;

            return [
                'value' => $division,
                'label' => $division,
                'can_delete' => $count === 0,
            ];
        }, $divisions);

        return Inertia::render('employees/index', [
            'employees' => EmployeeResource::collection($employees),
            'query' => $table->toArray(),
            'divisions' => $divisionOptions,
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
