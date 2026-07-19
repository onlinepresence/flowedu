<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Model
{
    protected $fillable = [
        'user_id',
        'lastname',
        'othernames',
        'phone_number',
        'gender',
        'profile_pic',
        'position_title',
        'office',
        'department_id',
        'faculty_id',
        'status',
        'date_of_appointment',
        'created_by',
        'ghana_card',
        'type',
    ];

    protected $casts = [
        'date_of_appointment' => 'date',
    ];

    protected static function booted()
    {
        static::saved(function (Admin $admin) {
            $admin->recordAssignmentHistory();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Admin subtype in DB: FK to user_roles.id (see ai/ADR_DATABASE_INFRASTRUCTURE.md).
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'type');
    }

    public function canAccessDepartment(?int $departmentId): bool
    {
        if (!$this->department_id && !$this->faculty_id) {
            return true; // Global admin
        }
        if ($this->department_id) {
            return $this->department_id === $departmentId;
        }
        if ($this->faculty_id) {
            if (!$departmentId) {
                return false;
            }
            return Department::where('id', $departmentId)->where('faculty_id', $this->faculty_id)->exists();
        }
        return false;
    }

    public function canAccessFaculty(?int $facultyId): bool
    {
        if (!$this->department_id && !$this->faculty_id) {
            return true; // Global admin
        }
        if ($this->faculty_id) {
            return $this->faculty_id === $facultyId;
        }
        if ($this->department_id) {
            if (!$facultyId) {
                return false;
            }
            return Department::where('id', $this->department_id)->where('faculty_id', $facultyId)->exists();
        }
        return false;
    }

    public function canAccessStudent(Student $student): bool
    {
        return $this->canAccessDepartment($student->department_id);
    }

    public function canAccessProgram(Program $program): bool
    {
        return $this->canAccessDepartment($program->department_id);
    }

    public function canAccessCourse(Course $course): bool
    {
        return $this->canAccessDepartment($course->program?->department_id);
    }

    public function officeHistories(): HasMany
    {
        return $this->hasMany(OfficeAssignmentHistory::class);
    }

    public function recordAssignmentHistory(): void
    {
        // 1. Check if there's an active history record.
        $active = $this->officeHistories()->where('status', 'active')->first();

        if ($active) {
            // If the active record has the exact same role (type), department, faculty, and office, do nothing.
            if ($active->role_id === $this->type
                && $active->department_id === $this->department_id
                && $active->faculty_id === $this->faculty_id
                && $active->office === $this->office
            ) {
                return;
            }

            // Otherwise, close the current active record.
            $active->update([
                'end_date' => now(),
                'status' => 'ended',
            ]);
        }

        // 2. Create a new active history record.
        if ($this->status === 'active' && $this->type) {
            $this->officeHistories()->create([
                'role_id' => $this->type,
                'department_id' => $this->department_id,
                'faculty_id' => $this->faculty_id,
                'office' => $this->office,
                'start_date' => now(),
                'status' => 'active',
            ]);
        }
    }

    public function closeAllAssignmentHistories(): void
    {
        $this->officeHistories()->where('status', 'active')->update([
            'end_date' => now(),
            'status' => 'ended',
        ]);
    }
}

