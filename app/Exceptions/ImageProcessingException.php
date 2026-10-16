<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ImageProcessingException extends BusinessRuleException
{
    public static function unreadable(): self
    {
        return new self('File tidak dapat dibaca sebagai gambar. Gunakan foto JPG, PNG, atau WebP.');
    }

    public static function notStored(): self
    {
        return new self('Foto gagal disimpan. Silakan coba lagi.');
    }
}
