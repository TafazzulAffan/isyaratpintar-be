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
        $connection = DB::connection()->getDriverName();

        // Step 1: Drop foreign keys from pbl_cases table
        if (Schema::hasTable('pbl_cases')) {
            if ($connection === 'pgsql') {
                DB::statement('ALTER TABLE "pbl_cases" DROP CONSTRAINT IF EXISTS "pbl_cases_pbl_level_id_foreign"');
            } elseif ($connection === 'mysql') {
                Schema::table('pbl_cases', function (Blueprint $table) {
                    if (Schema::hasColumn('pbl_cases', 'pbl_level_id')) {
                        try {
                            $table->dropForeign(['pbl_level_id']);
                        } catch (\Exception $e) {
                            // Foreign key may not exist
                        }
                    }
                });
            }
        }

        // Step 2: Drop foreign keys from assessments table
        if (Schema::hasTable('assessments')) {
            if ($connection === 'pgsql') {
                DB::statement('ALTER TABLE "assessments" DROP CONSTRAINT IF EXISTS "assessments_assessment_level_id_foreign"');
            } elseif ($connection === 'mysql') {
                Schema::table('assessments', function (Blueprint $table) {
                    if (Schema::hasColumn('assessments', 'assessment_level_id')) {
                        try {
                            $table->dropForeign(['assessment_level_id']);
                        } catch (\Exception $e) {
                            // Foreign key may not exist
                        }
                    }
                });
            }
        }

        // Step 3: Rename pbl_level_id to mata_pelajaran_id in pbl_cases
        if (Schema::hasTable('pbl_cases') && Schema::hasColumn('pbl_cases', 'pbl_level_id')) {
            Schema::table('pbl_cases', function (Blueprint $table) {
                $table->renameColumn('pbl_level_id', 'mata_pelajaran_id');
            });
        }

        // Step 4: Rename assessment_level_id to mata_pelajaran_id in assessments
        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'assessment_level_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->renameColumn('assessment_level_id', 'mata_pelajaran_id');
            });
        }

        // Step 5: Add foreign key constraints to pbl_cases
        if (Schema::hasTable('pbl_cases') && Schema::hasColumn('pbl_cases', 'mata_pelajaran_id')) {
            Schema::table('pbl_cases', function (Blueprint $table) {
                $table->foreign('mata_pelajaran_id')
                    ->references('id')
                    ->on('mata_pelajarans')
                    ->cascadeOnDelete();
            });
        }

        // Step 6: Add foreign key constraints to assessments
        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'mata_pelajaran_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->foreign('mata_pelajaran_id')
                    ->references('id')
                    ->on('mata_pelajarans')
                    ->cascadeOnDelete();
            });
        }

        // Step 7: Drop assessment_levels table
        Schema::dropIfExists('assessment_levels');

        // Step 8: Drop pbl_levels table
        Schema::dropIfExists('pbl_levels');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection()->getDriverName();

        // Recreate pbl_levels table
        Schema::create('pbl_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Recreate assessment_levels table
        Schema::create('assessment_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('level');
        });

        // Revert pbl_cases
        Schema::table('pbl_cases', function (Blueprint $table) use ($connection) {
            if ($connection === 'pgsql') {
                DB::statement('ALTER TABLE "pbl_cases" DROP CONSTRAINT IF EXISTS "pbl_cases_mata_pelajaran_id_foreign"');
            } elseif ($connection === 'mysql') {
                if (Schema::hasColumn('pbl_cases', 'mata_pelajaran_id')) {
                    $table->dropForeign(['mata_pelajaran_id']);
                }
            }
        });

        Schema::table('pbl_cases', function (Blueprint $table) {
            $table->renameColumn('mata_pelajaran_id', 'pbl_level_id');
            $table->foreign('pbl_level_id')->references('id')->on('pbl_levels')->cascadeOnDelete();
        });

        // Revert assessments
        Schema::table('assessments', function (Blueprint $table) use ($connection) {
            if ($connection === 'pgsql') {
                DB::statement('ALTER TABLE "assessments" DROP CONSTRAINT IF EXISTS "assessments_mata_pelajaran_id_foreign"');
            } elseif ($connection === 'mysql') {
                if (Schema::hasColumn('assessments', 'mata_pelajaran_id')) {
                    $table->dropForeign(['mata_pelajaran_id']);
                }
            }
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->renameColumn('mata_pelajaran_id', 'assessment_level_id');
            $table->foreign('assessment_level_id')->references('id')->on('assessment_levels')->cascadeOnDelete();
        });
    }
};
