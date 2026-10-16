<?php

declare(strict_types=1);

namespace App\Exceptions;

final class StaffAccountException extends BusinessRuleException
{
    public static function cannotDeactivateSelf(): self
    {
        return new self('Anda tidak dapat menonaktifkan akun Anda sendiri.');
    }

    public static function cannotChangeOwnRole(): self
    {
        return new self('Anda tidak dapat mengubah peran akun Anda sendiri.');
    }

    public static function cannotResetOwnPassword(): self
    {
        return new self('Untuk mengganti password Anda sendiri, gunakan halaman Pengaturan > Keamanan.');
    }

    public static function lastActiveManager(): self
    {
        return new self('Minimal harus ada satu akun Pengelola aktif. Tambahkan atau aktifkan Pengelola lain terlebih dahulu.');
    }

    public static function alreadyInactive(): self
    {
        return new self('Akun ini sudah nonaktif.');
    }

    public static function alreadyActive(): self
    {
        return new self('Akun ini sudah aktif.');
    }
}
