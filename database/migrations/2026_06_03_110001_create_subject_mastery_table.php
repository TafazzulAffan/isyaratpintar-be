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
        Schema::create('subject_mastery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->onDelete('cascade');
            
            $table->decimal('mastery_percentage', 5, 2)->default(0); // 0-100
            $table->unsignedInteger('assessments_completed')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->string('status')->default('poor'); // 'excellent', 'good', 'needs_help', 'poor'
            
            $table->timestamp('last_assessed_at')->nullable();
            $table->timestamp('calculated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();

            // Unique combination of user and subject
            $table->unique(['user_id', 'mata_pelajaran_id']);
            
            // Indexes for queries
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_mastery');
    }
};
