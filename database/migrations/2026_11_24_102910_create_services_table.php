<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // 0 = Sunday ... 6 = Saturday (Carbon convention)
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
            $table->unique(['branch_id', 'day_of_week', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
