<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use DateTimeInterface;

/**
 * Status peminjaman yang tampil, termasuk indikator turunan Terlambat dan
 * Belum Diambil (AGENTS.md 5.2). Dihitung saat ditampilkan, tidak disimpan.
 */
enum BookingDisplayStatus: string
{
    use HasOptions;

    case Scheduled = 'scheduled';
    case NotPickedUp = 'not_picked_up';
    case Active = 'active';
    case Overdue = 'overdue';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public static function resolve(
        BookingStatus $status,
        DateTimeInterface $startAt,
        DateTimeInterface $plannedReturnAt,
        DateTimeInterface $now,
    ): self {
        return match ($status) {
            BookingStatus::Scheduled => $startAt < $now ? self::NotPickedUp : self::Scheduled,
            BookingStatus::Active => $plannedReturnAt < $now ? self::Overdue : self::Active,
            BookingStatus::Completed => self::Completed,
            BookingStatus::Cancelled => self::Cancelled,
        };
    }

    /**
     * Pilihan filter daftar Peminjaman (hanya peminjaman berjalan).
     *
     * @return list<self>
     */
    public static function running(): array
    {
        return [self::Scheduled, self::Active, self::Overdue, self::NotPickedUp];
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Dijadwalkan',
            self::NotPickedUp => 'Belum Diambil',
            self::Active => 'Aktif',
            self::Overdue => 'Terlambat',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
