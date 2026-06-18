<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    protected $fillable = ['academic_year_id', 'expense_category_id', 'user_id', 'date', 'amount', 'notes', 'attachment_path'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        return Storage::disk($disk)->url($this->attachment_path);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
