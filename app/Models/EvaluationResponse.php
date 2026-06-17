<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationResponse extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'form_id',
        'student_id',
        'teacher_id',
        'student_department_id',
        'response_code',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    /**
     * Student user account (FK to users per schema dump).
     */
    public function studentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Teacher user account (FK to users per schema dump).
     */
    public function teacherUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function studentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'student_department_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ResponseDetail::class, 'response_id');
    }
}
