<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Exceptions\StaffAccountException;
use App\Models\User;
use App\Support\SessionTerminator;
use App\Support\StaffCredentials;
use App\Support\TemporaryPasswordGenerator;
use Illuminate\Support\Facades\DB;

final class ResetStaffPassword
{
    public function __construct(
        private readonly TemporaryPasswordGenerator $passwords,
        private readonly SessionTerminator $sessions,
    ) {}

    /**
     * @param  User|null  $actor  null bila dijalankan lewat perintah artisan
     */
    public function handle(User $staff, ?User $actor): StaffCredentials
    {
        if ($actor !== null && $staff->is($actor)) {
            throw StaffAccountException::cannotResetOwnPassword();
        }

        return DB::transaction(function () use ($staff, $actor): StaffCredentials {
            $staff = User::query()->lockForUpdate()->findOrFail($staff->id);
            $temporaryPassword = $this->passwords->generate();

            $staff->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
            ])->save();

            // Password lama dianggap bocor: semua sesi yang masih login diakhiri.
            $this->sessions->forUser($staff);

            // Password tidak pernah ikut dicatat, baik lama maupun baru.
            activity(ActivityLogName::Staff)
                ->performedOn($staff)
                ->causedBy($actor)
                ->event(ActivityEvent::PasswordReset->value)
                ->withProperties($actor === null ? ['via' => 'artisan'] : [])
                ->log("Mereset password akun petugas {$staff->name}");

            return new StaffCredentials($staff, $temporaryPassword);
        });
    }
}
