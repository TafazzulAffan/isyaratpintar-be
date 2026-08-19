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
        Schema::create('teacher_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');

            $table->string('insight_type'); // 'needs_attention', 'excelling', 'disengaged', 'at_risk'
            $table->text('message');
            $table->json('supporting_data')->nullable(); // risk scores, struggling subjects, etc

            $table->boolean('teacher_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();

            // Indexes for queries
            $table->index(['student_id', 'insight_type']);
            $table->index('insight_type');
            $table->index('teacher_acknowledged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_insights');
    }
};
