<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();
            $table->foreignId('field_visit_id')->nullable()->constrained('field_visits')->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Inspector

            $table->string('title');
            $table->string('category')->default('Safety & Quality'); // Safety & Quality, Facility & Grounds, Equipment & Machinery, Environmental
            $table->date('inspection_date');

            $table->decimal('compliance_score', 5, 2)->default(100.00); // 0.00 to 100.00%
            $table->string('result_grade')->default('Passed'); // Passed, Warning / Needs Improvement, Failed
            $table->string('status')->default('submitted'); // draft, submitted, under_review, approved, rejected

            $table->text('executive_summary')->nullable();
            $table->text('major_findings')->nullable();
            $table->text('corrective_recommendations')->nullable();

            // Supervisor review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('reviewer_remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_reports');
    }
};
