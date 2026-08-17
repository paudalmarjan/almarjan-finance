<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('pin')->nullable()->after('nis');
        });

        // Set default PIN '123456' to all existing students
        $defaultPin = \Illuminate\Support\Facades\Hash::make('123456');
        \Illuminate\Support\Facades\DB::table('students')->update(['pin' => $defaultPin]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};
