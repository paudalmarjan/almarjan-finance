<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentDetail extends Model
{
    protected $fillable = ['payment_transaction_id', 'type', 'reference_id', 'month_index', 'amount'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function studentAnnualFee(): BelongsTo
    {
        return $this->belongsTo(StudentAnnualFee::class, 'reference_id');
    }

    /**
     * Get the month name in Bahasa Indonesia based on the month_index (1-12, where 1 is Juli).
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Juli',
            2 => 'Agustus',
            3 => 'September',
            4 => 'Oktober',
            5 => 'November',
            6 => 'Desember',
            7 => 'Januari',
            8 => 'Februari',
            9 => 'Maret',
            10 => 'April',
            11 => 'Mei',
            12 => 'Juni'
        ];

        return $months[$this->month_index] ?? '';
    }
}
