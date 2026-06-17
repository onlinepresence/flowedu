<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSession extends Model
{
    protected $table = 'academic_sessions';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class, 'academic_session_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'academic_session_id');
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class, 'session_id');
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'session_id');
    }

    public static function activeSessionId(): ?int
    {
        $current = self::where('is_current', true)->first();
        if ($current) {
            return (int) $current->id;
        }
        return self::orderByDesc('id')->value('id') ? (int) self::orderByDesc('id')->value('id') : null;
    }
}
