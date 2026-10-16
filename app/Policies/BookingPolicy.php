<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Peminjaman dan foto bukti boleh dikelola Pengelola dan Admin (prd.md
 * section 9). Aturan status (mis. hanya Dijadwalkan yang bisa diedit) adalah
 * aturan bisnis dan dijaga di Action.
 */
final class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Ekspor Riwayat (FR-26): Admin dan Pengelola (prd.md section 9).
     */
    public function export(User $user): bool
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return true;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return true;
    }

    public function handover(User $user, Booking $booking): bool
    {
        return true;
    }

    public function extend(User $user, Booking $booking): bool
    {
        return true;
    }

    public function viewPhoto(User $user, Booking $booking): bool
    {
        return true;
    }
}
