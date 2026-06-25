<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'vendor_name',
        'description',
        'amount',
        'invoice_date',
        'due_date',
        'status',
        'file_path',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function expenditures(): HasMany
    {
        return $this->hasMany(Expenditure::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->expenditures()->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0.0, (float) $this->amount - $this->paid_amount);
    }
}
