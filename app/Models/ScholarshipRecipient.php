<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipRecipient extends Model
{
    protected $fillable = [
        'scholarship_id',
        'student_id',
        'academic_session_id',
        'amount_awarded',
        'award_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_awarded' => 'decimal:2',
            'award_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ScholarshipRecipient $recipient) {
            if (empty($recipient->academic_session_id)) {
                $recipient->academic_session_id = AcademicSession::activeSessionId();
            }
        });
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
