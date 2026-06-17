<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'is_active', 'initial_cash_balance'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'initial_cash_balance' => 'float',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function annualFeeComponents(): HasMany
    {
        return $this->hasMany(AnnualFeeComponent::class);
    }

    public function globalSppSetting(): HasOne
    {
        return $this->hasOne(GlobalSppSetting::class);
    }

    public function calculateEndingBalance(): float
    {
        $income = (float) \App\Models\PaymentTransaction::where('academic_year_id', $this->id)->sum('total_amount');
        $outcome = (float) \App\Models\Expense::whereBetween('date', [$this->start_date, $this->end_date])->sum('amount');
        return (float) $this->initial_cash_balance + $income - $outcome;
    }
}
