<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('student_group_id')->constrained('student_groups')->cascadeOnDelete();
            $table->foreignId('discount_category_id')->nullable()->constrained('discount_categories')->nullOnDelete();
            $table->decimal('discount_percentage', 5, 2)->default(0.00); // Snapshot diskon saat pendaftaran
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']); // Siswa hanya boleh terdaftar sekali per tahun ajaran
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
