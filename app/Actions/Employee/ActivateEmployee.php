<?php

declare(strict_types=1);

namespace App\Actions\Employee;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Exceptions\EmployeeException;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ActivateEmployee
{
    public function handle(Employee $employee, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $actor): Employee {
            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);

            if ($employee->is_active) {
                throw EmployeeException::alreadyActive();
            }

            $employee->forceFill(['is_active' => true])->save();

            activity(ActivityLogName::Employee)
                ->performedOn($employee)
                ->causedBy($actor)
                ->event(ActivityEvent::Activated->value)
                ->withChanges(['old' => ['is_active' => false], 'attributes' => ['is_active' => true]])
                ->log("Mengaktifkan karyawan {$employee->name}");

            return $employee;
        });
    }
}
