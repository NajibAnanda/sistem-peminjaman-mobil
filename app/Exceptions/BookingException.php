<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Support\DateFormat;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class BookingException extends BusinessRuleException
{
    private const MAX_LISTED_CONFLICTS = 3;

    private const RELOAD_HINT = 'Data kemungkinan baru saja diubah petugas lain. Muat ulang halaman untuk melihat data terbaru.';

    /**
     * Antarmuka hanya menawarkan transisi yang sah, jadi transisi yang ditolak
     * hampir selalu berarti data sudah diubah petugas lain (atau klik ganda).
     */
    public static function invalidTransition(BookingStatus $from, BookingStatus $to): self
    {
        return new self("Peminjaman ini sudah berstatus {$from->label()} sehingga tidak dapat diubah menjadi {$to->label()}. ".self::RELOAD_HINT);
    }

    public static function notEditable(BookingStatus $status): self
    {
        return new self("Jadwal hanya dapat diedit selama peminjaman berstatus Dijadwalkan (status saat ini: {$status->label()}). ".self::RELOAD_HINT);
    }

    public static function notExtendable(BookingStatus $status): self
    {
        return new self("Hanya peminjaman Aktif yang dapat diperpanjang (status saat ini: {$status->label()}). ".self::RELOAD_HINT);
    }

    /**
     * @param  Collection<int, Booking>  $conflicts
     */
    public static function employeeConflict(string $name, Collection $conflicts): self
    {
        $listed = $conflicts->take(self::MAX_LISTED_CONFLICTS)
            ->map(fn (Booking $booking): string => "{$booking->code} ({$booking->car->brand} {$booking->car->model} · {$booking->car->plate_number}, ".DateFormat::range($booking->start_at, $booking->planned_return_at).')')
            ->implode(', ');
        $remaining = $conflicts->count() - self::MAX_LISTED_CONFLICTS;
        $suffix = $remaining > 0 ? " dan {$remaining} lainnya" : '';

        return (new self("{$name} sudah memiliki peminjaman lain pada waktu yang sama: {$listed}{$suffix}. Pilih waktu lain."))
            ->forField('employee_id');
    }

    public static function employeeInactiveAtPickup(string $name): self
    {
        return new self("Pegawai {$name} sudah nonaktif sehingga serah terima ambil tidak dapat dilakukan. Batalkan peminjaman ini atau ganti pegawainya lewat Edit Jadwal.");
    }

    public static function carInactiveAtPickup(Car|string $car): self
    {
        $name = $car instanceof Car ? "{$car->brand} {$car->model} ({$car->plate_number})" : $car;

        return new self("Mobil {$name} sudah nonaktif sehingga serah terima ambil tidak dapat dilakukan. Pindahkan peminjaman ke mobil lain lewat Edit Jadwal.");
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
            ->implode(', ');

        $remaining = $conflicts->count() - self::MAX_LISTED_CONFLICTS;
        $suffix = $remaining > 0 ? " dan {$remaining} lainnya" : '';

        return (new self("Jadwal bentrok dengan peminjaman lain untuk mobil ini: {$listed}{$suffix}. Pilih waktu atau mobil lain."))
            ->forField('car_id');
    }

    public static function carNotSchedulable(Car|string $car, string $reason): self
    {
        $name = $car instanceof Car ? "{$car->brand} {$car->model} ({$car->plate_number})" : $car;

        return (new self("Mobil {$name} sedang {$reason} sehingga tidak dapat dijadwalkan."))->forField('car_id');
    }

    public static function employeeInactive(string $name): self
    {
        return (new self("Pegawai {$name} sudah nonaktif sehingga tidak dapat dijadwalkan."))->forField('employee_id');
    }

    public static function carUnderMaintenance(Car|string $car): self
    {
        $name = $car instanceof Car ? "{$car->brand} {$car->model} ({$car->plate_number})" : $car;

        return new self("Mobil {$name} sedang Maintenance sehingga serah terima ambil tidak dapat dilakukan. Pindahkan peminjaman ke mobil lain.");
    }

    public static function carStillInUse(Car|string $car, string $code): self
    {
        $name = $car instanceof Car ? "{$car->brand} {$car->model} ({$car->plate_number})" : $car;

        return new self("Mobil {$name} masih dipakai peminjaman {$code} yang belum dikembalikan. Serah terima ambil dapat dilakukan setelah mobil dikembalikan.");
    }

    public static function extensionNotLater(CarbonInterface $currentPlannedReturn): self
    {
        return (new self('Rencana kembali baru harus lebih lambat dari rencana saat ini ('.DateFormat::dateTime($currentPlannedReturn).').'))
            ->forField('new_planned_return_at');
    }
}
