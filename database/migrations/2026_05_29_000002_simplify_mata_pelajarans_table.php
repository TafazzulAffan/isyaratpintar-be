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
        // Drop level_number column if it exists and any related indices
        if (Schema::hasTable('mata_pelajarans') && Schema::hasColumn('mata_pelajarans', 'level_number')) {
            Schema::table('mata_pelajarans', function (Blueprint $table) {
                $table->dropColumn('level_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore level_number column if table exists and column doesn't
        if (Schema::hasTable('mata_pelajarans') && !Schema::hasColumn('mata_pelajarans', 'level_number')) {
            Schema::table('mata_pelajarans', function (Blueprint $table) {
                $table->unsignedInteger('level_number')->unique()->after('id');
                $table->index('level_number');
            });
        }
    }
};
