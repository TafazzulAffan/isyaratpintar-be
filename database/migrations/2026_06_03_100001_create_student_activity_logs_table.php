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
        Schema::create('student_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // 'lesson_view', 'assessment_start', 'answer_submitted', etc
            $table->string('entity_type')->nullable(); // 'Lesson', 'Assessment', 'Task', 'Question'
            $table->unsignedBigInteger('entity_id')->nullable(); // lesson_id, assessment_id, etc
            $table->json('metadata')->nullable(); // Additional data like score, duration, etc
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'logged_at']);
            $table->index('activity_type');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_activity_logs');
    }
};
