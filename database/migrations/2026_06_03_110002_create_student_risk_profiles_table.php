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
        Schema::create('student_risk_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();

            // Risk Factor Scores (0-100)
            $table->decimal('attendance_risk', 5, 2)->default(50);
            $table->decimal('performance_risk', 5, 2)->default(50);
            $table->decimal('engagement_risk', 5, 2)->default(50);
            $table->decimal('subject_mastery_risk', 5, 2)->default(50);

            // Overall Score & Level
            $table->decimal('overall_risk_score', 5, 2)->default(50); // Weighted average (0-100)
            $table->string('risk_level')->default('medium'); // 'low', 'medium', 'high', 'critical'

            // Problem Areas
            $table->json('struggling_subjects')->nullable(); // array of mata_pelajaran_id
            $table->json('weak_topics')->nullable(); // array of topic areas

            // Recommendations
            $table->json('recommendations')->nullable(); // {subject_id => recommendation_text}

            $table->timestamp('calculated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();

            // Indexes
            $table->index('risk_level');
            $table->index('overall_risk_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_risk_profiles');
    }
};
