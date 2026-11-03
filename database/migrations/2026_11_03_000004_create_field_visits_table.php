<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_code')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('geographical_area_id')->nullable()->constrained('geographical_areas')->nullOnDelete();

            $table->dateTime('scheduled_at');
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled, missed
            $table->string('purpose')->default('routine_inspection'); // routine_inspection, audit, maintenance, survey, emergency

            // Check-in tracking
            $table->dateTime('check_in_at')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->integer('check_in_distance_meters')->nullable();
            $table->boolean('is_within_geofence')->nullable();
            $table->string('check_in_photo')->nullable();

            // Check-out tracking
            $table->dateTime('check_out_at')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->integer('duration_minutes')->nullable();

            // Notes and sign-off
            $table->text('visit_summary')->nullable();
            $table->string('client_representative_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_visits');
    }
};
