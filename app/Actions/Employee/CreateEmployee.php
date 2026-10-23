<?php

declare(strict_types=1);

namespace App\Actions\Employee;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateEmployee
{
    /**
     * @param  array{name: string, division: string, phone: string}  $data
     */
    public function handle(array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($data, $actor): Employee {
            $employee = Employee::query()->create([...$data, 'is_active' => true]);

            activity(ActivityLogName::Employee)
                ->performedOn($employee)
                ->causedBy($actor)
                ->event(ActivityEvent::Created->value)
                ->withChanges(['attributes' => $employee->only(['name', 'division', 'phone'])])
                ->log("Menambah karyawan {$employee->name}");

            return $employee;
        });
    }
}
