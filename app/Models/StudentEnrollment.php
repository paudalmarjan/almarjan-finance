<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'student_group_id',
        'discount_category_id',
        'discount_percentage',
        'enrollment_type',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function studentGroup(): BelongsTo
    {
        return $this->belongsTo(StudentGroup::class);
    }

    public function discountCategory(): BelongsTo
    {
        return $this->belongsTo(DiscountCategory::class);
    }

    public function studentAnnualFees(): HasMany
    {
        return $this->hasMany(StudentAnnualFee::class);
    }
}
