<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Concerns\ListsBookingCodes;

final class EmployeeException extends BusinessRuleException
{
    use ListsBookingCodes;

    /**
     * @param  list<string>  $codes
     */
    public static function hasOngoingBookings(array $codes): self
    {
        return new self('Karyawan tidak dapat dinonaktifkan karena masih memiliki peminjaman yang dijadwalkan atau aktif: '.self::listCodes($codes).'. Selesaikan atau batalkan peminjaman tersebut terlebih dahulu.');
    }

    public static function alreadyInactive(): self
    {
        return new self('Karyawan ini sudah nonaktif.');
    }

    public static function alreadyActive(): self
    {
        return new self('Karyawan ini sudah aktif.');
    }
}
