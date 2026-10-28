<?php

declare(strict_types=1);

namespace App\Actions\Employee;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateDivision
{
    public function handle(string $name, User $actor): Division
    {
        return DB::transaction(function () use ($name, $actor): Division {
            $division = Division::create(['name' => $name]);

            activity(ActivityLogName::Employee)
                ->causedBy($actor)
                ->performedOn($division)
                ->event(ActivityEvent::Created->value)
                ->log("Menambahkan divisi: {$name}");

            return $division;
        });
    }
}
