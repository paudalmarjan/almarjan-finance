<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomeCategory extends Model
{
    protected $fillable = ['name', 'description'];

    public function generalIncomes(): HasMany
    {
        return $this->hasMany(GeneralIncome::class);
    }
}
