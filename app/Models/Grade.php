<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = [
        'result_slip_id',
        'student_id',
        'teacher_id',
        'class_score',
        'exam_score',
        'attendance_score',
        'midsem_score',
        'project_score',
    ];

    protected $casts = [
        'class_score' => 'decimal:2',
        'exam_score' => 'decimal:2',
        'attendance_score' => 'decimal:2',
        'midsem_score' => 'decimal:2',
        'project_score' => 'decimal:2',
    ];

    public function resultSlip(): BelongsTo
    {
        return $this->belongsTo(ResultSlip::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
