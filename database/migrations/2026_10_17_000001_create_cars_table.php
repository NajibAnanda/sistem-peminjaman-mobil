<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 15)->unique();
            $table->string('brand', 50);
            $table->string('model', 50);
            // Hanya kondisi operasional; "Dipinjam" diturunkan dari peminjaman aktif.
            $table->string('operational_status', 20)->default('operational');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'operational_status']);
        });

        Schema::create('car_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('path');
            $table->timestamps();

            $table->unique(['car_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_photos');
        Schema::dropIfExists('cars');
    }
};
