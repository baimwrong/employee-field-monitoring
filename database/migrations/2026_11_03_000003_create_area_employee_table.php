<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('geographical_area_id')->constrained('geographical_areas')->onDelete('cascade');
            $table->string('role_in_area')->default('Field Officer'); // Lead Inspector, Field Officer, Specialist
            $table->date('assigned_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->text('assignment_notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'geographical_area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_employee');
    }
};
