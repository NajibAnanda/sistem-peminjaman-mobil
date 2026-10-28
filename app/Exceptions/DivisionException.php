<?php

declare(strict_types=1);

namespace App\Exceptions;

final class DivisionException extends BusinessRuleException
{
    public static function cannotDeleteDefault(string $name): self
    {
        return new self("Divisi {$name} merupakan divisi bawaan sistem dan tidak dapat dihapus.");
    }

    public static function stillInUse(string $name, int $count): self
    {
        return new self("Divisi {$name} tidak dapat dihapus karena masih digunakan oleh {$count} pegawai.");
    }
}
