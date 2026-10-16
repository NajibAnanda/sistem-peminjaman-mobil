<?php

declare(strict_types=1);

namespace App\Enums;

enum StaffRole: string
{
    case Manager = 'manager';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'Pengelola',
            self::Admin => 'Admin',
        };
    }
}
