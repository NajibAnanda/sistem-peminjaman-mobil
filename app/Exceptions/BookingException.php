<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Support\DateFormat;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class BookingException extends BusinessRuleException
{
    private const MAX_LISTED_CONFLICTS = 3;

    public static function invalidTransition(BookingStatus $from, BookingStatus $to): self
    {
        return new self("Peminjaman berstatus {$from->label()} tidak dapat diubah menjadi {$to->label()}.");
    }

    public static function notEditable(BookingStatus $status): self
    {
        return new self("Jadwal hanya dapat diedit selama peminjaman berstatus Dijadwalkan (status saat ini: {$status->label()}).");
    }

    public static function notExtendable(BookingStatus $status): self
    {
        return new self("Hanya peminjaman Aktif yang dapat diperpanjang (status saat ini: {$status->label()}).");
    }

    /**
     * FR-12: menyebut kode dan rentang peminjaman yang bertabrakan.
     *
     * @param  Collection<int, Booking>  $conflicts
     */
    public static function conflict(Collection $conflicts): self
    {
        $listed = $conflicts->take(self::MAX_LISTED_CONFLICTS)
            ->map(fn (Booking $booking): string => "{$booking->code} ({$booking->employee->name}, ".DateFormat::range($booking->start_at, $booking->planned_return_at).')')
            ->implode('; ');

        $remaining = $conflicts->count() - self::MAX_LISTED_CONFLICTS;
        $suffix = $remaining > 0 ? " dan {$remaining} lainnya" : '';

        return (new self("Jadwal bentrok dengan peminjaman lain untuk mobil ini: {$listed}{$suffix}. Pilih waktu atau mobil lain."))
            ->forField('car_id');
    }

    public static function carNotSchedulable(string $plateNumber, string $reason): self
    {
        return (new self("Mobil {$plateNumber} sedang {$reason} sehingga tidak dapat dijadwalkan."))->forField('car_id');
    }

    public static function employeeInactive(string $name): self
    {
        return (new self("Karyawan {$name} sudah nonaktif sehingga tidak dapat dijadwalkan."))->forField('employee_id');
    }

    public static function carUnderMaintenance(string $plateNumber): self
    {
        return new self("Mobil {$plateNumber} sedang Maintenance sehingga serah terima ambil tidak dapat dilakukan. Pindahkan peminjaman ke mobil lain.");
    }

    public static function carStillInUse(string $plateNumber, string $code): self
    {
        return new self("Mobil {$plateNumber} masih dipakai peminjaman {$code} yang belum dikembalikan. Serah terima ambil dapat dilakukan setelah mobil dikembalikan.");
    }

    public static function extensionNotLater(CarbonInterface $currentPlannedReturn): self
    {
        return (new self('Rencana kembali baru harus lebih lambat dari rencana saat ini ('.DateFormat::dateTime($currentPlannedReturn).').'))
            ->forField('new_planned_return_at');
    }
}
