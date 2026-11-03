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
        Schema::table('locations', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
            $table->index('location_type');
            $table->index('is_active');
        });

        Schema::table('field_visits', function (Blueprint $table) {
            $table->index('scheduled_at');
            $table->index('status');
        });

        Schema::table('inspection_reports', function (Blueprint $table) {
            $table->index('inspection_date');
            $table->index('status');
            $table->index('result_grade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
        });

        Schema::table('inspection_reports', function (Blueprint $table) {
            $table->dropIndex(['inspection_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['result_grade']);
        });

        Schema::table('field_visits', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at']);
            $table->dropIndex(['status']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['location_type']);
            $table->dropIndex(['is_active']);
        });
    }
};
