<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "Yatim", "Dhuafa", "Anak Guru"
            $table->decimal('percentage', 5, 2)->default(0.00); // 0.00 to 100.00
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_categories');
    }
};
