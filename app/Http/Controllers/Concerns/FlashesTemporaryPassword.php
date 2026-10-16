<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Support\StaffCredentials;
use Inertia\Inertia;

trait FlashesTemporaryPassword
{
    /**
     * Password sementara dikirim lewat flash Inertia agar hanya tampil sekali
     * dan tidak tersimpan di riwayat browser.
     */
    private function flashTemporaryPassword(StaffCredentials $credentials, string $message): void
    {
        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => $message],
            'temporaryPassword' => [
                'name' => $credentials->user->name,
                'email' => $credentials->user->email,
                'password' => $credentials->temporaryPassword,
            ],
        ]);
    }
}
