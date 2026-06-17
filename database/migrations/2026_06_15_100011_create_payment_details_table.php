<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->constrained('payment_transactions')->cascadeOnDelete();
            $table->string('type'); // 'Annual' atau 'SPP'
            $table->foreignId('reference_id')->nullable()->constrained('student_annual_fees')->cascadeOnDelete(); // Terisi jika type = 'Annual'
            $table->integer('month_index')->nullable(); // Terisi jika type = 'SPP' (1 = Juli, 2 = Agustus, ..., 12 = Juni)
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
