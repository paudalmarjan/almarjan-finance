<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['nis', 'name', 'nickname', 'parent_name', 'phone_number', 'status'];

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Get enrollment for a specific academic year.
     */
    public function enrollmentForYear($academicYearId)
    {
        return $this->enrollments()->where('academic_year_id', $academicYearId)->first();
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
