<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Kode peminjaman PMJ-YYYYMM-NNNN, urut per bulan pembuatan (AGENTS.md 5.9).
 */
final class BookingCodeGenerator
{
    private const PREFIX = 'PMJ';

    private const TABLE = 'booking_code_sequences';

    /**
     * Wajib dipanggil di dalam transaksi: baris penghitung dikunci sampai
     * transaksi selesai, sehingga dua penjadwalan bersamaan tidak pernah
     * mendapat nomor yang sama.
     */
    public function next(?CarbonInterface $at = null): string
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('BookingCodeGenerator::next() harus dipanggil di dalam DB::transaction().');
        }

        $period = ($at ?? now())->format('Ym');

        DB::table(self::TABLE)->insertOrIgnore(['period' => $period, 'last_number' => 0]);

        $last = (int) DB::table(self::TABLE)->where('period', $period)->lockForUpdate()->value('last_number');
        $next = $last + 1;

        DB::table(self::TABLE)->where('period', $period)->update(['last_number' => $next]);

        return sprintf('%s-%s-%04d', self::PREFIX, $period, $next);
    }
}
