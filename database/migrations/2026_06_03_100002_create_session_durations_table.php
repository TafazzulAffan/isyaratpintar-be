<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('session_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0); // Will be calculated
            $table->string('session_type')->default('learning'); // 'learning', 'assessment', 'general'
            $table->unsignedBigInteger('entity_id')->nullable(); // lesson_id or attempt_id for context
            $table->timestamps();

            // Indexes for analytics queries
            $table->index(['user_id', 'started_at']);
            $table->index(['user_id', 'ended_at']);
            $table->index('session_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_durations');
    }
};
