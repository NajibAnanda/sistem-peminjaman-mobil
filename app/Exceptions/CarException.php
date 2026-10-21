<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Concerns\ListsBookingCodes;

final class CarException extends BusinessRuleException
{
    use ListsBookingCodes;

    /**
     * @param  list<string>  $codes
     */
    public static function hasOngoingBookings(array $codes): self
    {
        return new self('Mobil tidak dapat dinonaktifkan karena masih memiliki peminjaman yang dijadwalkan atau aktif: '.self::listCodes($codes).'. Selesaikan atau batalkan peminjaman tersebut terlebih dahulu.');
    }

    public static function borrowedCannotEnterMaintenance(string $code): self
    {
        return new self("Mobil sedang dipinjam ({$code}) sehingga tidak dapat diubah ke Maintenance. Tunggu sampai mobil dikembalikan.");
    }

    public static function alreadyInactive(): self
    {
        return new self('Mobil ini sudah nonaktif. Muat ulang halaman untuk melihat data terbaru.');
    }

    public static function alreadyActive(): self
    {
        return new self('Mobil ini sudah aktif. Muat ulang halaman untuk melihat data terbaru.');
    }

    public static function inactiveCannotChangeStatus(): self
    {
        return new self('Aktifkan mobil terlebih dahulu sebelum mengubah status operasionalnya.');
    }
}
