<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff\Concerns;

use App\Enums\StaffRole;

trait ProvidesStaffData
{
    /**
     * Data akun yang sudah tervalidasi, dengan tipe yang jelas untuk Action.
     *
     * @return array{name: string, email: string, role: StaffRole}
     */
    public function staffData(): array
    {
        return [
            'name' => $this->string('name')->value(),
            'email' => $this->string('email')->value(),
            'role' => StaffRole::from($this->string('role')->value()),
        ];
    }
}
