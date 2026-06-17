<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = ['name'];

    public function groups(): HasMany
    {
        return $this->hasMany(StudentGroup::class);
    }

    public function annualFeeComponents(): HasMany
    {
        return $this->hasMany(AnnualFeeComponent::class);
    }
}
