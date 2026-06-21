<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSavingsTransaction extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'user_id',
        'type',
        'amount',
        'transaction_date',
        'notes',
        'receipt_number',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
