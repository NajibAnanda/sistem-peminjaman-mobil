<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Exceptions\StaffAccountException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ActivateStaffAccount
{
    /**
     * @param  User|null  $actor  null bila dijalankan lewat perintah artisan
     */
    public function handle(User $staff, ?User $actor): User
    {
        return DB::transaction(function () use ($staff, $actor): User {
            $staff = User::query()->lockForUpdate()->findOrFail($staff->id);

            if ($staff->is_active) {
                throw StaffAccountException::alreadyActive();
            }

            $staff->forceFill(['is_active' => true])->save();

            activity(ActivityLogName::Staff)
                ->performedOn($staff)
                ->causedBy($actor)
                ->event(ActivityEvent::Activated->value)
                ->withChanges(['old' => ['is_active' => false], 'attributes' => ['is_active' => true]])
                ->withProperties($actor === null ? ['via' => 'artisan'] : [])
                ->log("Mengaktifkan akun petugas {$staff->name}");

            return $staff;
        });
    }
}
