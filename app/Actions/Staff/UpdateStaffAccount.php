<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\StaffRole;
use App\Exceptions\StaffAccountException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateStaffAccount
{
    private const LOGGED_ATTRIBUTES = ['name', 'email', 'role'];

    /**
     * @param  array{name: string, email: string, role: StaffRole}  $data
     */
    public function handle(User $staff, array $data, User $actor): User
    {
        return DB::transaction(function () use ($staff, $data, $actor): User {
            // Kunci Pengelola aktif lebih dulu (urutan id tetap) agar dua
            // perubahan bersamaan tidak bisa menghabiskan semua Pengelola.
            $activeManagerIds = User::query()->activeManagers()->orderBy('id')->lockForUpdate()->pluck('id');

            $staff = User::query()->lockForUpdate()->findOrFail($staff->id);
            $newRole = $data['role'];

            if ($newRole !== $staff->role) {
                if ($staff->is($actor)) {
                    throw StaffAccountException::cannotChangeOwnRole();
                }

                $leavesNoManager = $staff->isManager()
                    && $staff->is_active
                    && $activeManagerIds->reject(fn (int $id): bool => $id === $staff->id)->isEmpty();

                if ($leavesNoManager) {
                    throw StaffAccountException::lastActiveManager();
                }
            }

            $before = $staff->only(self::LOGGED_ATTRIBUTES);

            $staff->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $newRole,
            ]);

            $changed = array_keys($staff->getDirty());
            $staff->save();

            if ($changed !== []) {
                activity(ActivityLogName::Staff)
                    ->performedOn($staff)
                    ->causedBy($actor)
                    ->event(ActivityEvent::Updated->value)
                    ->withChanges([
                        'old' => array_intersect_key($before, array_flip($changed)),
                        'attributes' => $staff->only($changed),
                    ])
                    ->log("Mengubah akun petugas {$staff->name}");
            }

            return $staff;
        });
    }
}
