<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema peminjaman lengkap sesuai prd.md section 8. Dibuat di Fase 3 agar
 * aturan "tidak boleh nonaktif bila punya peminjaman berjalan" bisa diuji;
 * logika peminjaman dikerjakan di Fase 4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('car_id')->constrained()->restrictOnDelete();
            $table->string('purpose');
            $table->dateTime('start_at');
            $table->dateTime('planned_return_at');
            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->string('status', 20);
            $table->text('return_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('picked_up_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // prd.md section 8.3
            $table->index(['car_id', 'status', 'start_at', 'planned_return_at']);
            $table->index(['status', 'planned_return_at']);
            $table->index(['status', 'start_at']);
            $table->index('start_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
