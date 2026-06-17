<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryRecord extends Model
{
    protected $fillable = [
        'index_number',
        'fullname',
        'program_id',
        'academic_session_id',
        'offense',
        'action_taken',
        'comments',
        'date_of_action',
        'return_date',
        'return_status',
    ];

    protected $casts = [
        'date_of_action' => 'date',
        'return_date' => 'date',
        'return_status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (DisciplinaryRecord $record) {
            if (empty($record->academic_session_id)) {
                $record->academic_session_id = AcademicSession::activeSessionId();
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
