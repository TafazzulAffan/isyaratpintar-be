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
        // Skip if already migrated
        if (Schema::hasTable('mata_pelajarans')) {
            return;
        }

        // Only proceed if levels table exists
        if (!Schema::hasTable('levels')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        // Step 1: Drop foreign key constraint on lessons table
        Schema::table('lessons', function (Blueprint $table) use ($connection) {
            if ($connection === 'pgsql') {
                DB::statement('ALTER TABLE "lessons" DROP CONSTRAINT IF EXISTS "lessons_level_id_foreign"');
            } elseif ($connection === 'mysql') {
                $table->dropForeign(['level_id']);
            }
        });

        // Step 2: Rename column level_id to mata_pelajaran_id
        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('level_id', 'mata_pelajaran_id');
        });

        // Step 3: Rename table levels to mata_pelajarans
        Schema::rename('levels', 'mata_pelajarans');

        // Step 4: Add the new foreign key constraint
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreign('mata_pelajaran_id')
                ->references('id')
                ->on('mata_pelajarans')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip if already reverted
        if (!Schema::hasTable('mata_pelajarans')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        // Step 1: Drop foreign key constraint
        Schema::table('lessons', function (Blueprint $table) use ($connection) {
            if ($connection === 'pgsql') {
                DB::statement('ALTER TABLE "lessons" DROP CONSTRAINT IF EXISTS "lessons_mata_pelajaran_id_foreign"');
            } elseif ($connection === 'mysql') {
                $table->dropForeign(['mata_pelajaran_id']);
            }
        });

        // Step 2: Rename column back
        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('mata_pelajaran_id', 'level_id');
        });

        // Step 3: Rename table back
        Schema::rename('mata_pelajarans', 'levels');

        // Step 4: Add old foreign key back
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreign('level_id')
                ->references('id')
                ->on('levels')
                ->cascadeOnDelete();
        });
    }
};
