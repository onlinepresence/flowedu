<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    protected $fillable = [
        'program_id',
        'level',
        'session_id',
        'tuition_fee',
        'library_fee',
        'lab_fee',
        'medical_fee',
        'sports_fee',
        'examination_fee',
        'registration_fee',
        'ict_fee',
        'id_card_fee',
        'facility_maintenance_fee',
        'utility_fee',
        'field_trip_fee',
        'internship_fee',
        'src_dues',
        'total_amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'tuition_fee' => 'decimal:2',
            'library_fee' => 'decimal:2',
            'lab_fee' => 'decimal:2',
            'medical_fee' => 'decimal:2',
            'sports_fee' => 'decimal:2',
            'examination_fee' => 'decimal:2',
            'registration_fee' => 'decimal:2',
            'ict_fee' => 'decimal:2',
            'id_card_fee' => 'decimal:2',
            'facility_maintenance_fee' => 'decimal:2',
            'utility_fee' => 'decimal:2',
            'field_trip_fee' => 'decimal:2',
            'internship_fee' => 'decimal:2',
            'src_dues' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'fee_structure_id');
    }
}
