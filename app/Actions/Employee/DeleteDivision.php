<?php

declare(strict_types=1);

namespace App\Actions\Employee;

use App\Enums\ActivityLogName;
use App\Exceptions\DivisionException;
use App\Models\Division;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteDivision
{
    public function handle(string $name, User $actor): void
    {
        DB::transaction(function () use ($name, $actor): void {
            $employeeCount = Employee::query()->where('division', $name)->count();

            if ($employeeCount > 0) {
                throw DivisionException::stillInUse($name, $employeeCount);
            }

            Division::query()->where('name', $name)->delete();

            activity(ActivityLogName::Employee)
                ->causedBy($actor)
                ->log("Menghapus divisi: {$name}");
        });
    }
}
