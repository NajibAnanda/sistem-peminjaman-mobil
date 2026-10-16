<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 30)->unique();
            $table->string('name');
            $table->string('division', 50);
            $table->string('phone', 15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'division']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
