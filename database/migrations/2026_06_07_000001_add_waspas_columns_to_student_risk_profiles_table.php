<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_risk_profiles', function (Blueprint $table) {
            $table->decimal('waspas_score', 8, 6)->nullable()->after('overall_risk_score');
            $table->decimal('wsm_score', 8, 6)->nullable()->after('waspas_score');
            $table->decimal('wpm_score', 8, 6)->nullable()->after('wsm_score');
            $table->unsignedInteger('waspas_rank')->nullable()->after('wpm_score');

            $table->index('waspas_score');
            $table->index('waspas_rank');
        });
    }

    public function down(): void
    {
        Schema::table('student_risk_profiles', function (Blueprint $table) {
            $table->dropIndex(['waspas_score']);
            $table->dropIndex(['waspas_rank']);
            $table->dropColumn(['waspas_score', 'wsm_score', 'wpm_score', 'waspas_rank']);
        });
    }
};
