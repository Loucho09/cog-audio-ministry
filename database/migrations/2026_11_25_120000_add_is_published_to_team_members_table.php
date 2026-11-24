<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// gallery_images already has is_published (since its create migration), so only team_members needs it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('is_featured');
        });

        // Everything that exists today is already live: keep it visible.
        DB::table('team_members')->update(['is_published' => true]);
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
