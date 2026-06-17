<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnnualFeeComponent extends Model
{
    protected $fillable = ['academic_year_id', 'level_id', 'name', 'amount', 'target_type', 'sort_order'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function studentAnnualFees(): HasMany
    {
        return $this->hasMany(StudentAnnualFee::class);
    }
}
