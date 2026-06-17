<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ResultSlip extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'slip_number',
        'teacher_id',
        'program_id',
        'course_id',
        'academic_session_id',
        'level',
        'semester',
        'status',
        'review_comments',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'semester' => 'integer',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ResultSlip $slip) {
            if (empty($slip->slip_number)) {
                $slip->slip_number = 'SLIP-' . date('Y') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }
}
