<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geographical_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('city')->nullable();
            $table->string('province_state')->nullable();
            $table->decimal('center_lat', 10, 7)->default(-6.2088000); // Default Jakarta region for realistic test
            $table->decimal('center_lng', 10, 7)->default(106.8456000);
            $table->integer('radius_meters')->default(3000); // in meters
            $table->json('boundary_geojson')->nullable();
            $table->string('color_code', 20)->default('#2563eb');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geographical_areas');
    }
};
