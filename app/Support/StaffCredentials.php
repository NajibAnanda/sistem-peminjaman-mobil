<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Hasil pembuatan akun atau reset password: password sementara hanya ada di
 * objek ini dan tidak pernah disimpan dalam bentuk teks.
 */
final readonly class StaffCredentials
{
    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {}
}
