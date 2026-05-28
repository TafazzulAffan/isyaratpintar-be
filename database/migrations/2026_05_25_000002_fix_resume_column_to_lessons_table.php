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
        Schema::table('lessons', function (Blueprint $table) {
            // Drop the wrong column if it exists
            if (Schema::hasColumn('lessons', 'resume_url')) {
                $table->dropColumn('resume_url');
            }
            // Add the correct column if it doesn't exist
            if (!Schema::hasColumn('lessons', 'resume')) {
                $table->longText('resume')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('resume');
            $table->string('resume_url')->nullable();
        });
    }
};
