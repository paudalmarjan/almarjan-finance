<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCategory extends Model
{
    protected $fillable = ['name', 'percentage'];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
