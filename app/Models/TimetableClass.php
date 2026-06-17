<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableClass extends Model
{
    protected $table = 'timetable_classes';

    /** @var list<string> */
    protected $fillable = [
        'timetable_id',
        'program_id',
        'course_id',
        'teacher_id',
        'day',
        'start_time',
        'end_time',
        'venue',
    ];

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
