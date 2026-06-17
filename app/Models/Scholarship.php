<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scholarship extends Model
{
    protected $fillable = [
        'name',
        'type',
        'amount',
        'duration_semesters',
        'expiry_date',
        'coverage_type',
        'coverage_components',
        'description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'duration_semesters' => 'integer',
            'expiry_date' => 'date',
            'coverage_components' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(ScholarshipRecipient::class);
    }
}
