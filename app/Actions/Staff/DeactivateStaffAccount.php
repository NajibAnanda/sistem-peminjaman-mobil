<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Exceptions\StaffAccountException;
use App\Models\User;
use App\Support\SessionTerminator;
use Illuminate\Support\Facades\DB;

final class DeactivateStaffAccount
{
    public function __construct(private readonly SessionTerminator $sessions) {}

    public function handle(User $staff, User $actor): User
    {
        if ($staff->is($actor)) {
            throw StaffAccountException::cannotDeactivateSelf();
        }

        return DB::transaction(function () use ($staff, $actor): User {
            // Kunci Pengelola aktif lebih dulu (urutan id tetap) agar dua
            // penonaktifan bersamaan tidak bisa menghabiskan semua Pengelola.
            $activeManagerIds = User::query()->activeManagers()->orderBy('id')->lockForUpdate()->pluck('id');

            $staff = User::query()->lockForUpdate()->findOrFail($staff->id);

            if (! $staff->is_active) {
                throw StaffAccountException::alreadyInactive();
            }

            if ($staff->isManager() && $activeManagerIds->reject(fn (int $id): bool => $id === $staff->id)->isEmpty()) {
                throw StaffAccountException::lastActiveManager();
            }

            $staff->forceFill(['is_active' => false])->save();

            // FR-05: akun yang dinonaktifkan langsung keluar dari semua sesinya.
            $this->sessions->forUser($staff);

            activity(ActivityLogName::Staff)
                ->performedOn($staff)
                ->causedBy($actor)
                ->event(ActivityEvent::Deactivated->value)
                ->withChanges(['old' => ['is_active' => true], 'attributes' => ['is_active' => false]])
                ->log("Menonaktifkan akun petugas {$staff->name}");

            return $staff;
        });
    }
}
