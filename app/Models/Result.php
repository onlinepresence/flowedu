<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'academic_session_id',
        'score',
        'grade',
        'grade_points',
        'entered_by',
        'entered_date',
        'result_token',
        'teacher_id',
        'result_slip_id',
        'admin_amended',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'grade_points' => 'decimal:2',
            'entered_date' => 'date',
            'admin_amended' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function resultSlip(): BelongsTo
    {
        return $this->belongsTo(ResultSlip::class);
    }

    public function grade()
    {
        return $this->hasOne(Grade::class, 'student_id', 'student_id')
            ->where('result_slip_id', $this->result_slip_id);
    }

    public function academicInformation(): HasMany
    {
        return $this->hasMany(AcademicInformation::class);
    }
}
