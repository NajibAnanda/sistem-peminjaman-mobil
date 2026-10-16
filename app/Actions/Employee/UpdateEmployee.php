<?php

declare(strict_types=1);

namespace App\Actions\Employee;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateEmployee
{
    /**
     * @param  array{employee_number: string, name: string, division: string, phone: string}  $data
     */
    public function handle(Employee $employee, array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor): Employee {
            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $before = $employee->only(array_keys($data));

            $employee->fill($data);
            $changed = array_keys($employee->getDirty());
            $employee->save();

            if ($changed !== []) {
                activity(ActivityLogName::Employee)
                    ->performedOn($employee)
                    ->causedBy($actor)
                    ->event(ActivityEvent::Updated->value)
                    ->withChanges([
                        'old' => array_intersect_key($before, array_flip($changed)),
                        'attributes' => $employee->only($changed),
                    ])
                    ->log("Mengubah data karyawan {$employee->name}");
            }

            return $employee;
        });
    }
}
