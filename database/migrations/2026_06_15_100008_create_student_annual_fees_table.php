<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_annual_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->foreignId('annual_fee_component_id')->constrained('annual_fee_components')->cascadeOnDelete();
            $table->decimal('amount', 12, 2); // Nilai bersih setelah diskon (jika diskon berlaku untuk komponen ini)
            $table->boolean('is_excluded')->default(false); // Pengecualian, misal: tidak mengambil seragam
            $table->timestamps();

            $table->unique(['student_enrollment_id', 'annual_fee_component_id'], 'student_enrollment_component_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_annual_fees');
    }
};
