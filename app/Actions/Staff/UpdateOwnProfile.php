<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\User;

final class UpdateOwnProfile
{
    /**
     * Petugas hanya boleh mengubah namanya sendiri; email diubah oleh
     * Pengelola lewat Kelola Petugas (FR-03).
     */
    public function handle(User $user, string $name): User
    {
        $oldName = $user->name;

        $user->forceFill(['name' => $name])->save();

        if ($oldName !== $name) {
            activity(ActivityLogName::Staff)
                ->performedOn($user)
                ->causedBy($user)
                ->event(ActivityEvent::Updated->value)
                ->withChanges(['old' => ['name' => $oldName], 'attributes' => ['name' => $name]])
                ->log("{$name} mengubah nama di profil");
        }

        return $user;
    }
}
