<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Staff\ActivateStaffAccount;
use App\Actions\Staff\ResetStaffPassword;
use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Jalan keluar bila tidak ada Pengelola yang bisa login (FR-05), karena
 * aplikasi tidak memiliki fitur lupa password lewat email.
 */
final class ResetManagerPassword extends Command
{
    protected $signature = 'fleet:reset-manager-password
                            {email : Email akun Pengelola yang terkunci}';

    protected $description = 'Mereset password akun Pengelola menjadi password sementara yang wajib diganti saat login';

    public function handle(ResetStaffPassword $resetPassword, ActivateStaffAccount $activateAccount): int
    {
        $argument = $this->argument('email');
        $email = is_string($argument) ? mb_strtolower(trim($argument)) : '';

        $manager = User::query()
            ->where('email', $email)
            ->where('role', StaffRole::Manager)
            ->first();

        if ($manager === null) {
            $this->error("Akun Pengelola dengan email {$email} tidak ditemukan.");

            return self::FAILURE;
        }

        $status = $manager->is_active ? 'aktif' : 'nonaktif';
        $this->line("Akun ditemukan: {$manager->name} <{$manager->email}> ({$status}).");

        if (! $this->confirm('Reset password akun ini? Semua sesi akun yang sedang login akan diakhiri.')) {
            $this->warn('Dibatalkan. Tidak ada perubahan.');

            return self::FAILURE;
        }

        if (! $manager->is_active) {
            if ($this->confirm('Akun ini nonaktif. Aktifkan kembali sekaligus?', true)) {
                $manager = $activateAccount->handle($manager, null);
            } else {
                $this->warn('Akun tetap nonaktif dan belum bisa login sampai diaktifkan.');
            }
        }

        $credentials = $resetPassword->handle($manager, null);

        $this->newLine();
        $this->info("Password sementara: {$credentials->temporaryPassword}");
        $this->warn('Password ini hanya ditampilkan sekali dan wajib diganti saat login berikutnya.');

        return self::SUCCESS;
    }
}
