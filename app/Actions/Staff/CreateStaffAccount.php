<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\StaffRole;
use App\Models\User;
use App\Support\StaffCredentials;
use App\Support\TemporaryPasswordGenerator;
use Illuminate\Support\Facades\DB;

final class CreateStaffAccount
{
    public function __construct(private readonly TemporaryPasswordGenerator $passwords) {}

    /**
     * @param  array{name: string, email: string, role: StaffRole}  $data
     * @param  User|null  $actor  null bila dijalankan lewat perintah artisan darurat
     */
    public function handle(array $data, ?User $actor = null): StaffCredentials
    {
        return DB::transaction(function () use ($data, $actor): StaffCredentials {
            $temporaryPassword = $this->passwords->generate();

            $staff = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => $temporaryPassword,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            activity(ActivityLogName::Staff)
                ->performedOn($staff)
                ->causedBy($actor)
                ->event(ActivityEvent::Created->value)
                ->withProperties($actor === null ? ['via' => 'artisan'] : [])
                ->withChanges(['attributes' => $staff->only(['name', 'email', 'role', 'is_active'])])
                ->log("Membuat akun petugas {$staff->name}");

            return new StaffCredentials($staff, $temporaryPassword);
        });
    }
}
