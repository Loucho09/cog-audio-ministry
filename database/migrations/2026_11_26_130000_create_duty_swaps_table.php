<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('duty_assignment_id')->constrained()->cascadeOnDelete();
            // Restrict, like duty_assignments.user_id: a member with duty history cannot be hard-deleted.
            $table->foreignId('offered_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');
            $table->dateTime('claimed_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['duty_assignment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_swaps');
    }
};
