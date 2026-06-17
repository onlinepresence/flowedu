<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendanceSheet extends Model
{
    protected $fillable = [
        'teacher_id',
        'course_id',
        'academic_session_id',
        'semester_id',
        'class_date',
        'file_path',
        'original_name',
    ];

    protected function casts(): array
    {
        return [
            'class_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TeacherAttendanceSheet $sheet) {
            if (empty($sheet->academic_session_id)) {
                $sheet->academic_session_id = AcademicSession::activeSessionId();
            }
            if (empty($sheet->semester_id)) {
                $sheet->semester_id = Semester::activeSemesterId();
            }
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}
