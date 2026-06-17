<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('global_spp_settings', function (Blueprint $table) {
            $table->decimal('spp_amount', 12, 2)->default(0.00)->after('academic_year_id');
            $table->decimal('komite_amount', 12, 2)->default(0.00)->after('spp_amount');
        });

        // Populate existing data
        DB::table('global_spp_settings')->update([
            'spp_amount' => DB::raw('amount'),
            'komite_amount' => 0.00,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_spp_settings', function (Blueprint $table) {
            $table->dropColumn(['spp_amount', 'komite_amount']);
        });
    }
};
