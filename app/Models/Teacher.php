<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'lastname',
        'othernames',
        'title',
        'ghana_card',
        'profile_pic',
        'gender',
        'date_of_birth',
        'nationality',
        'contact_address',
        'phone_number',
        'staff_id',
        'department_id',
        'office_location',
        'office_hours',
        'rank',
        'qualification',
        'specialization',
        'orcid_id',
        'google_scholar_url',
        'employment_type',
        'years_experience',
        'cv',
        'certificate',
        'id_document',
        'emergency_name',
        'emergency_phone',
        'research_interests',
        'date_of_appointment',
        'password_reset_required',
        'is_onboarded',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_appointment' => 'date',
        'password_reset_required' => 'boolean',
        'is_onboarded' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function courseMaterials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function teacherRoles(): HasMany
    {
        return $this->hasMany(TeacherRole::class);
    }

    public function attendanceSheets(): HasMany
    {
        return $this->hasMany(TeacherAttendanceSheet::class);
    }

    public function supervisions(): HasMany
    {
        return $this->hasMany(TeachingPracticeSupervision::class);
    }

    public function sharedLessonPlans(): HasMany
    {
        return $this->hasMany(SharedLessonPlan::class);
    }
}
