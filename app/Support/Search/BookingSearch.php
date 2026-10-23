<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\Booking;
use App\Models\User;

/**
 * Peminjaman: kode, nama/NIP karyawan, atau plat nomor (scope search yang sama
 * dengan tabel Peminjaman & Riwayat). Terbaru di atas.
 */
final class BookingSearch implements SearchCategory
{
    public function key(): string
    {
        return 'bookings';
    }

    public function label(): string
    {
        return 'Peminjaman';
    }

    public function allows(User $user): bool
    {
        return $user->can('viewAny', Booking::class);
    }

    public function search(string $term, int $limit): array
    {
        return array_values(Booking::query()
            ->with(['employee:id,name', 'car:id,plate_number'])
            ->search($term)
            ->orderByDesc('start_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'title' => $booking->code,
                'subtitle' => "{$booking->employee->name} · {$booking->car->plate_number} · {$booking->displayStatus()->label()}",
                'url' => route('bookings.show', $booking, absolute: false),
            ])
            ->all());
    }
}
