<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('assessment_levels')) {
            return;
        }

        // Check if level column already exists
        if (Schema::hasColumn('assessment_levels', 'level')) {
            return;
        }

        Schema::table('assessment_levels', function (Blueprint $table) {
            $table->unsignedSmallInteger('level')->nullable()->after('id');
        });

        // Backfill level using id as a sensible default for existing rows
        DB::statement('UPDATE assessment_levels SET level = id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('assessment_levels')) {
            return;
        }

        Schema::table('assessment_levels', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
