<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAnnualFee extends Model
{
    protected $fillable = ['student_enrollment_id', 'annual_fee_component_id', 'amount', 'is_excluded'];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_excluded' => 'boolean',
    ];

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function annualFeeComponent(): BelongsTo
    {
        return $this->belongsTo(AnnualFeeComponent::class);
    }

    public function paymentDetails(): HasMany
    {
        return $this->hasMany(PaymentDetail::class, 'reference_id')->where('type', 'Annual');
    }

    /**
     * Get total amount paid for this specific component.
     */
    public function getPaidAmountAttribute(): float
    {
        return (float) $this->paymentDetails()->sum('amount');
    }

    /**
     * Get remaining balance for this component.
     */
    public function getBalanceAttribute(): float
    {
        if ($this->is_excluded) {
            return 0.0;
        }
        return max(0.0, (float)$this->amount - $this->paid_amount);
    }
}
