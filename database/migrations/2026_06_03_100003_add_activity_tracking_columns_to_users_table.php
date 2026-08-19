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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('email_verified_at');
            $table->unsignedInteger('total_learning_minutes')->default(0)->after('last_activity_at');
            $table->unsignedInteger('activities_count')->default(0)->after('total_learning_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_activity_at');
            $table->dropColumn('total_learning_minutes');
            $table->dropColumn('activities_count');
        });
    }
};
