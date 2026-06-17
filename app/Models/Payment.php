<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Legacy/ajax `payments` table (per admin/ajax/finance.php). Not {@see FeePayment}.
 */
class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'fee_structure_id',
        'amount_paid',
        'payment_method',
        'payment_date',
        'reference_number',
        'status',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
