<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geographical_area_id')->constrained('geographical_areas')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('client_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('geofence_radius_meters')->default(150); // Acceptable check-in radius
            $table->string('location_type')->default('Retail'); // Retail, Office, Warehouse, Factory, Infrastructure, Site
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
