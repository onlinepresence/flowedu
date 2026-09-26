<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Gate;

#[Fillable([
    'name',
    'username',
    'email',
    'email_verified_at',
    'type',
    'password',
    'must_change_password',
    'user_secret',
    'active',
    'staff_leave_type_id',
])]
#[Hidden(['password', 'remember_token', 'user_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'must_change_password' => 'boolean',
            'staff_leave_type_id' => 'integer',
        ];
    }

    /**
     * Forced password change: provisioned accounts (seeded admin, invitees,
     * admin resets) must pick their own secret first. Cleared only by a
     * self-initiated change, never by admin edits.
     */
    public function requiresPasswordChange(): bool
    {
        return (bool) ($this->must_change_password ?? false);
    }

    public function staffLeaveType(): BelongsTo
    {
        return $this->belongsTo(StaffLeaveType::class, 'staff_leave_type_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function invoicesCreated(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function expendituresRecorded(): HasMany
    {
        return $this->hasMany(Expenditure::class, 'recorded_by');
    }

    public function systemAudits(): HasMany
    {
        return $this->hasMany(SystemAudit::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class, 'created_by');
    }

    public function isTeacherActive(): bool
    {
        if ($this->type === 'teacher') {
            return true;
        }

        if ($this->type === 'admin' && session('active_portal_role') === 'teacher') {
            $this->loadMissing('teacher');
            return $this->teacher !== null;
        }

        return false;
    }

    public function adminRoleSlug(): ?string
    {
        if ($this->type !== 'admin') {
            return null;
        }

        $this->loadMissing('admin.role');

        return $this->admin?->role?->name;
    }

    public function isAdminOwner(): bool
    {
        return $this->adminRoleSlug() === 'owner';
    }

    /**
     * Whether this admin may start an impersonation session (owner or system_admin role).
     */
    public function canStartImpersonation(): bool
    {
        if ($this->type !== 'admin') {
            return false;
        }

        return in_array($this->adminRoleSlug(), ['owner', 'system_admin'], true);
    }

    /**
     * @return list<string>
     */
    public function adminPermissionSlugs(): array
    {
        if ($this->type === 'admin') {
            if (session('active_portal_role') === 'teacher') {
                return [];
            }

            $this->loadMissing('admin.role');
            $permissions = $this->admin?->role?->permissions;

            if (! is_array($permissions)) {
                return [];
            }

            return array_values(array_filter($permissions, fn ($p) => is_string($p)));
        }

        return [];
    }

    public function hasAdminPermission(string $slug): bool
    {
        if (session('active_portal_role') === 'teacher') {
            return false;
        }

        if ($this->isAdminOwner()) {
            return true;
        }

        return in_array($slug, $this->adminPermissionSlugs(), true);
    }

    public function canAdmin(string $ability): bool
    {
        return Gate::forUser($this)->allows($ability);
    }

    /**
     * Legacy pages/licence-required.php: show "View licence" for admin cohort (owner, hod, dean, etc.).
     */
    public function canViewLicenceSubscriptionLink(): bool
    {
        if ($this->type !== 'admin') {
            return false;
        }

        $this->loadMissing('admin');

        if ($this->isAdminOwner()) {
            return true;
        }

        $slug = $this->adminRoleSlug();

        if ($slug === null && $this->admin !== null) {
            return true;
        }

        return in_array($slug, ['hod', 'dean', 'registrar'], true);
    }

    public function teacherPermissions(): array
    {
        if (!$this->isTeacherActive()) {
            return [];
        }

        $this->loadMissing('teacher.teacherRoles');
        if ($this->teacher === null) {
            return [];
        }

        $roles = $this->teacher->teacherRoles
            ->where('status', 'active')
            ->pluck('role')
            ->all();

        // If the teacher has no assigned roles, they get a default fallback (full access)
        if (empty($roles)) {
            return ['courses', 'students', 'assessments', 'communication'];
        }

        return \App\Models\TeacherPortalRole::query()
            ->whereIn('name', $roles)
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique()
            ->all();
    }

    public function hasTeacherPermission(string $permission): bool
    {
        if (!$this->isTeacherActive()) {
            return false;
        }

        return in_array($permission, $this->teacherPermissions(), true);
    }
}
