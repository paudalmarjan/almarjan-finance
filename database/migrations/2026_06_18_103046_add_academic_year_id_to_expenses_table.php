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
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
        });

        // Backfill existing expenses
        $expenses = DB::table('expenses')->get();
        $academicYears = DB::table('academic_years')->get();

        foreach ($expenses as $expense) {
            $matchedYear = null;
            foreach ($academicYears as $year) {
                if ($expense->date >= $year->start_date && $expense->date <= $year->end_date) {
                    $matchedYear = $year->id;
                    break;
                }
            }

            // Fallback to active year or first year if no exact match (e.g., prep expenses)
            if (!$matchedYear) {
                $active = $academicYears->firstWhere('status', 'Active');
                $matchedYear = $active ? $active->id : ($academicYears->first() ? $academicYears->first()->id : null);
            }

            if ($matchedYear) {
                DB::table('expenses')->where('id', $expense->id)->update(['academic_year_id' => $matchedYear]);
            }
        }

        // Now make it non-nullable if desired, but we can keep it nullable if there's a chance it's null
        // For strictness, if all expenses got an ID, we can enforce it:
        // Schema::table('expenses', function (Blueprint $table) {
        //     $table->unsignedBigInteger('academic_year_id')->nullable(false)->change();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }
};
