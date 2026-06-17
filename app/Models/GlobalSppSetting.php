<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalSppSetting extends Model
{
    protected $fillable = ['academic_year_id', 'amount', 'spp_amount', 'komite_amount'];

    protected $casts = [
        'amount' => 'decimal:2',
        'spp_amount' => 'decimal:2',
        'komite_amount' => 'decimal:2',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
