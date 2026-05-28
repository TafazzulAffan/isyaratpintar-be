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
        // Drop foreign key constraint first
        if (Schema::hasColumn('attempt_answers', 'selected_option_id')) {
            Schema::table('attempt_answers', function (Blueprint $table) {
                $table->dropForeign(['selected_option_id']);
                $table->dropColumn('selected_option_id');
            });
        }

        // Drop existing options table
        Schema::dropIfExists('options');

        // Recreate options table with UUID primary key
        Schema::create('options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('label')->comment('a, b, c, d, etc');
            $table->text('text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            
            $table->index('question_id');
            $table->index('is_correct');
        });

        // Add new selected_option_id column with UUID type
        if (!Schema::hasColumn('attempt_answers', 'selected_option_id')) {
            Schema::table('attempt_answers', function (Blueprint $table) {
                $table->uuid('selected_option_id')->nullable()->after('question_id');
                $table->foreign('selected_option_id')->references('id')->on('options')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint first
        if (Schema::hasColumn('attempt_answers', 'selected_option_id')) {
            Schema::table('attempt_answers', function (Blueprint $table) {
                if (Schema::hasForeignKey('attempt_answers', 'attempt_answers_selected_option_id_foreign')) {
                    $table->dropForeign(['selected_option_id']);
                }
            });
        }

        // Drop options table
        Schema::dropIfExists('options');

        // Recreate options table with original structure
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('label')->comment('a, b, c, d, etc');
            $table->text('text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            
            $table->index('question_id');
            $table->index('is_correct');
        });

        // Restore attempt_answers with original foreign key
        if (Schema::hasColumn('attempt_answers', 'selected_option_id')) {
            Schema::table('attempt_answers', function (Blueprint $table) {
                $table->dropColumn('selected_option_id');
            });
        }
        
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->foreignId('selected_option_id')->after('question_id')->constrained('options')->onDelete('cascade');
        });
    }
};
