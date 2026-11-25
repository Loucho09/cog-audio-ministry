<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('join_applications', function (Blueprint $table) {
            $table->dropColumn(['interests', 'experience', 'availability']);
        });
    }

    public function down(): void
    {
        Schema::table('join_applications', function (Blueprint $table) {
            $table->json('interests')->nullable();
            $table->text('experience')->nullable();
            $table->text('availability')->nullable();
        });
    }
};
