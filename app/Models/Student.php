<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'graduated' => 'boolean',
        'is_new' => 'boolean',
        'approved' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function academicInformation(): HasMany
    {
        return $this->hasMany(AcademicInformation::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function parentGuardians(): HasMany
    {
        return $this->hasMany(ParentGuardian::class);
    }

    public function medicalHistory(): HasOne
    {
        return $this->hasOne(MedicalHistory::class);
    }

    public function clearances(): HasMany
    {
        return $this->hasMany(StudentClearance::class);
    }

    public function legacyPayments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scholarshipRecipients(): HasMany
    {
        return $this->hasMany(ScholarshipRecipient::class);
    }

    public function supervisions(): HasMany
    {
        return $this->hasMany(TeachingPracticeSupervision::class);
    }
}
