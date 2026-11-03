<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_report_id')->constrained('inspection_reports')->onDelete('cascade');
            $table->string('item_name');
            $table->string('category')->default('Standard');
            $table->string('status')->default('pass'); // pass, fail, na
            $table->string('severity')->default('low'); // low, medium, high, critical
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_report_id')->constrained('inspection_reports')->onDelete('cascade');
            $table->string('photo_path');
            $table->string('caption')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_photos');
        Schema::dropIfExists('inspection_items');
    }
};
