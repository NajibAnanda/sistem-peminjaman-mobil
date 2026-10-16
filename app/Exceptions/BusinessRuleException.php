<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis. Pesannya selalu Bahasa Indonesia dan aman
 * ditampilkan ke pengguna. Dipetakan di bootstrap/app.php: bila terkait satu
 * field form, tampil sebagai error field tersebut; selain itu sebagai toast.
 */
abstract class BusinessRuleException extends RuntimeException
{
    private ?string $field = null;

    public function field(): ?string
    {
        return $this->field;
    }

    public function forField(string $field): static
    {
        $this->field = $field;

        return $this;
    }
}
