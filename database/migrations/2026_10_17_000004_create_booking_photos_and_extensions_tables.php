<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('stage', 20);
            $table->string('source', 20);
            $table->string('path');
            // Waktu server saat foto disimpan (FR-17), bukan waktu dari perangkat.
            $table->dateTime('taken_at');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Tepat satu foto per tahap serah terima.
            $table->unique(['booking_id', 'stage']);
        });

        Schema::create('booking_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->dateTime('previous_planned_return_at');
            $table->dateTime('new_planned_return_at');
            $table->text('reason');
            $table->foreignId('extended_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        // Penghitung nomor urut kode PMJ-YYYYMM-NNNN per bulan. Baris dikunci saat
        // membuat kode agar dua penjadwalan bersamaan tidak mendapat nomor sama.
        Schema::create('booking_code_sequences', function (Blueprint $table) {
            $table->string('period', 6)->primary();
            $table->unsignedInteger('last_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_code_sequences');
        Schema::dropIfExists('booking_extensions');
        Schema::dropIfExists('booking_photos');
    }
};
