<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil audit performa dengan ±10.000 peminjaman (Fase 7):
 * - Jadwal Armada minggu lama membaca ribuan baris Selesai hanya untuk
 *   menyaring picked_up_at; dengan kolom itu di dalam index, penyaringan
 *   terjadi di index dan hanya baris yang cocok yang dibaca.
 * - Log Aktivitas dengan filter jenis data + periode butuh index gabungan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['status', 'returned_at', 'picked_up_at']);
            $table->dropIndex(['status', 'returned_at']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['log_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['log_name', 'created_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['status', 'returned_at']);
            $table->dropIndex(['status', 'returned_at', 'picked_up_at']);
        });
    }
};
