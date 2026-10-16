<?php

declare(strict_types=1);

namespace App\Enums;

enum PhotoStage: string
{
    case Pickup = 'pickup';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Serah terima ambil',
            self::Return => 'Serah terima kembali',
        };
    }
}
