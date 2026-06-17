<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMaterial extends Model
{
    protected $fillable = [
        'course_id',
        'teacher_id',
        'academic_session_id',
        'semester_id',
        'title',
        'description',
        'file_path',
        'file_type',
        'status',
        'published',
        'approved_by',
        'approved_date',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'approved_date' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CourseMaterial $material) {
            if (empty($material->academic_session_id)) {
                $material->academic_session_id = AcademicSession::activeSessionId();
            }
            if (empty($material->semester_id)) {
                $material->semester_id = Semester::activeSemesterId();
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
