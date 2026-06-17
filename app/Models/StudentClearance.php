<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClearance extends Model
{
    protected $casts = [
        'cleared_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StudentClearance $clearance) {
            if (empty($clearance->academic_session_id)) {
                $clearance->academic_session_id = AcademicSession::activeSessionId();
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function clearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
