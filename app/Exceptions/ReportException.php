<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ReportException extends BusinessRuleException
{
    public static function pdfRowsExceeded(int $count, int $max): self
    {
        return new self("Jumlah data untuk ekspor PDF melebihi batas ({$count} baris, maksimal {$max} baris). Silakan persempit rentang tanggal atau gunakan format Excel / CSV.");
    }
}
