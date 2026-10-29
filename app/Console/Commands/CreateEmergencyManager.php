<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Staff\CreateStaffAccount;
use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Perintah artisan untuk membuat akun Pengelola darurat (Prompt 8 & PRD 13.3)
 * bila seluruh akun Pengelola terkunci atau sistem baru disiapkan.
 */
final class CreateEmergencyManager extends Command
{
    protected $signature = 'fleet:create-emergency-manager
                            {--name= : Nama lengkap akun Pengelola darurat}
                            {--email= : Alamat email akun Pengelola darurat}';

    protected $description = 'Membuat akun Pengelola darurat baru dengan password sementara yang wajib diganti saat login';

    public function handle(CreateStaffAccount $createStaff): int
    {
        $nameOption = $this->option('name');
        $name = is_string($nameOption) && trim($nameOption) !== ''
            ? trim($nameOption)
            : (string) $this->ask('Nama lengkap Pengelola', 'Pengelola Darurat');

        $emailOption = $this->option('email');
        $email = is_string($emailOption) && trim($emailOption) !== ''
            ? mb_strtolower(trim($emailOption))
            : mb_strtolower(trim((string) $this->ask('Alamat email Pengelola')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tidak valid.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error("Alamat email {$email} sudah digunakan oleh akun lain.");

            return self::FAILURE;
        }

        $this->line('Data akun yang akan dibuat:');
        $this->line("- Nama : {$name}");
        $this->line("- Email: {$email}");
        $this->line('- Role : Pengelola (Manager)');

        if (! $this->confirm('Apakah Anda yakin ingin membuat akun Pengelola darurat ini?')) {
            $this->warn('Dibatalkan. Tidak ada akun yang dibuat.');

            return self::FAILURE;
        }

        $credentials = $createStaff->handle([
            'name' => $name,
            'email' => $email,
            'role' => StaffRole::Manager,
        ], null);

        $this->newLine();
        $this->info('Akun Pengelola darurat berhasil dibuat!');
        $this->info("Email              : {$credentials->user->email}");
        $this->info("Password sementara : {$credentials->temporaryPassword}");
        $this->warn('Password ini hanya ditampilkan sekali dan wajib diganti saat login pertama kali.');

        return self::SUCCESS;
    }
}
