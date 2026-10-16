<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis aktivitas yang dicatat (kolom `event` pada log aktivitas).
 */
enum ActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deactivated = 'deactivated';
    case Activated = 'activated';
    case PasswordReset = 'password_reset';
    case PasswordChanged = 'password_changed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Dibuat',
            self::Updated => 'Diubah',
            self::Deactivated => 'Dinonaktifkan',
            self::Activated => 'Diaktifkan',
            self::PasswordReset => 'Password direset',
            self::PasswordChanged => 'Password diganti',
        };
    }
}
