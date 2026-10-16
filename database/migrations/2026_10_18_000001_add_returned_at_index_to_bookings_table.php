<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal Armada mencari peminjaman Selesai berdasarkan waktu kembali
 * aktualnya; tanpa index ini query tersebut memindai seluruh riwayat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['status', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status', 'returned_at']);
        });
    }
};
