<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolLicence extends Model
{
    protected $fillable = [
        'school_id',
        'max_active_students',
        'licence_start',
        'licence_end',
        'support_until',
        'notes',
        'external_ref',
        'licence_key',
        'core_timetable',
        'core_attendance',
        'core_memos',
        'core_impersonation',
        'module_finance',
        'module_staff_hr',
        'module_reports',
        'module_evaluations',
        'module_student_welfare',
        'module_progression',
        'module_system_admin',
        'module_teacher_tools',
        'module_practicum',
    ];

    protected $casts = [
        'licence_start' => 'date',
        'licence_end' => 'date',
        'support_until' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'core_timetable' => 'boolean',
        'core_attendance' => 'boolean',
        'core_memos' => 'boolean',
        'core_impersonation' => 'boolean',
        'module_finance' => 'boolean',
        'module_staff_hr' => 'boolean',
        'module_reports' => 'boolean',
        'module_evaluations' => 'boolean',
        'module_student_welfare' => 'boolean',
        'module_progression' => 'boolean',
        'module_system_admin' => 'boolean',
        'module_teacher_tools' => 'boolean',
        'module_practicum' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
