<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('mata_pelajaran_id')->constrained('kelas')->cascadeOnDelete();
        });

        Schema::table('pbl_cases', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('mata_pelajaran_id')->constrained('kelas')->cascadeOnDelete();
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('mata_pelajaran_id')->constrained('kelas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });

        Schema::table('pbl_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });
    }
};
