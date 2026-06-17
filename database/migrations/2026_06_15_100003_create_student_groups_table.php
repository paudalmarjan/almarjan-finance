<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('levels')->cascadeOnDelete();
            $table->string('name'); // e.g. "KB-A", "TKA-B"
            $table->timestamps();
            
            $table->unique(['level_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_groups');
    }
};
