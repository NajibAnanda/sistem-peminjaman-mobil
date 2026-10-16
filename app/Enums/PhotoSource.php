<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PhotoSource: string
{
    use HasOptions;

    case Camera = 'camera';
    case Upload = 'upload';

    public function label(): string
    {
        return match ($this) {
            self::Camera => 'Kamera',
            self::Upload => 'Unggah file',
        };
    }
}
