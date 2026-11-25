<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duty_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('guest_name', 100)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        DB::table('duty_assignments')->whereNull('user_id')->delete();

        Schema::table('duty_assignments', function (Blueprint $table) {
            $table->dropColumn('guest_name');
        });

        Schema::table('duty_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
