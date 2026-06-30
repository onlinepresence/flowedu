<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Memo extends Model
{
    protected $fillable = [
        'title',
        'content',
        'sender_id',
        'sender_entity_type',
        'sender_entity_id',
        'recipient_type',
        'recipient_entity_id',
        'recipient_role_id',
        'confidentiality_level',
        'status',
        'signing_user_id',
        'route_sequentially',
        'cc_recipients',
    ];

    protected function casts(): array
    {
        return [
            'route_sequentially' => 'boolean',
            'cc_recipients' => 'array',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function signingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signing_user_id');
    }

    public function recipientRole(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'recipient_role_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MemoAttachment::class);
    }

    public function trackingLogs(): HasMany
    {
        return $this->hasMany(MemoTracking::class)->orderBy('created_at', 'asc');
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(MemoSignatory::class);
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(MemoReadReceipt::class);
    }

    /**
     * Resolve all active eligible recipient users for this memo.
     */
    public function resolveTargetRecipients()
    {
        $query = User::query()->where('active', true);

        switch ($this->recipient_type) {
            case 'user':
                return $query->where('id', $this->recipient_entity_id)->get();

            case 'role':
                return $query->whereHas('admin', fn($q) => $q->where('type', $this->recipient_role_id))->get();

            case 'department':
                if (!$this->recipient_entity_id) {
                    return collect();
                }

                if ($this->confidentiality_level === 'confidential') {
                    $dept = Department::query()->find($this->recipient_entity_id);
                    if ($dept && $dept->hod) {
                        return $query->where('id', $dept->hod)->get();
                    }
                    return collect();
                }

                return $query->where(function ($q) {
                    $q->whereHas('admin', fn($a) => $a->where('department_id', $this->recipient_entity_id))
                      ->orWhereHas('teacher', fn($t) => $t->where('department_id', $this->recipient_entity_id))
                      ->orWhereHas('nonTeachingStaff', fn($ns) => $ns->where('department_id', $this->recipient_entity_id));
                    
                    if ($this->confidentiality_level === 'public') {
                        $q->orWhereHas('student', fn($s) => $s->where('department_id', $this->recipient_entity_id));
                    }
                })->get();

            case 'faculty':
                if (!$this->recipient_entity_id) {
                    return collect();
                }

                if ($this->confidentiality_level === 'confidential') {
                     $faculty = Faculty::query()->find($this->recipient_entity_id);
                     if ($faculty && $faculty->dean_id) {
                         return $query->where('id', $faculty->dean_id)->get();
                     }
                     return collect();
                }

                return $query->where(function ($q) {
                    $q->whereHas('admin', fn($a) => $a->where('faculty_id', $this->recipient_entity_id))
                      ->orWhereHas('teacher.department', fn($d) => $d->where('faculty_id', $this->recipient_entity_id))
                      ->orWhereHas('nonTeachingStaff.department', fn($d) => $d->where('faculty_id', $this->recipient_entity_id));

                    if ($this->confidentiality_level === 'public') {
                        $q->orWhereHas('student.department', fn($d) => $d->where('faculty_id', $this->recipient_entity_id));
                    }
                })->get();

            default:
                return collect();
        }
    }

    /**
     * Resolve the readable name of the sender entity.
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->sender_entity_type === 'department' && $this->sender_entity_id) {
            $dept = Department::query()->find($this->sender_entity_id);
            return $dept ? $dept->name . ' Department' : 'Unknown Department';
        }
        
        if ($this->sender_entity_type === 'faculty' && $this->sender_entity_id) {
            $fac = Faculty::query()->find($this->sender_entity_id);
            return $fac ? $fac->name . ' Faculty' : 'Unknown Faculty';
        }

        return $this->sender ? ($this->sender->name ?? $this->sender->username) : 'System';
    }

    /**
     * Resolve the readable name of the recipient.
     */
    public function getRecipientNameAttribute(): string
    {
        switch ($this->recipient_type) {
            case 'user':
                $user = User::query()->find($this->recipient_entity_id);
                return $user ? ($user->name ?? $user->username) : 'Unknown User';
            case 'department':
                $dept = Department::query()->find($this->recipient_entity_id);
                return $dept ? $dept->name . ' Department' : 'Unknown Department';
            case 'faculty':
                $fac = Faculty::query()->find($this->recipient_entity_id);
                return $fac ? $fac->name . ' Faculty' : 'Unknown Faculty';
            case 'role':
                return $this->recipientRole ? $this->recipientRole->display_name : 'Unknown Role';
            default:
                return 'Unknown';
        }
    }

    /**
     * Determine if a user is allowed to view this memo.
     */
    public function canBeViewedBy(User $user): bool
    {
        // Owner and System Admin can always view everything
        if ($user->isAdminOwner() || $user->adminRoleSlug() === 'system_admin') {
            return true;
        }

        // CC check: If user is CC'd, they can view
        if ($this->cc_recipients) {
            $cc = is_string($this->cc_recipients) ? json_decode($this->cc_recipients, true) : $this->cc_recipients;
            if (is_array($cc)) {
                // Check users
                if (isset($cc['users']) && in_array($user->id, $cc['users'])) {
                    return true;
                }
                // Check departments
                if (isset($cc['departments']) && !empty($cc['departments'])) {
                    $userDeptId = null;
                    if ($user->admin) $userDeptId = $user->admin->department_id;
                    elseif ($user->teacher) $userDeptId = $user->teacher->department_id;
                    elseif ($user->nonTeachingStaff) $userDeptId = $user->nonTeachingStaff->department_id;
                    elseif ($user->student) $userDeptId = $user->student->department_id;

                    if ($userDeptId && in_array((int)$userDeptId, array_map('intval', $cc['departments']))) {
                        return true;
                    }
                }
                // Check roles
                if (isset($cc['roles']) && !empty($cc['roles'])) {
                    $userRole = $user->adminRoleSlug();
                    if ($userRole) {
                        $roleModel = UserRole::query()->where('name', $userRole)->first();
                        if ($roleModel && in_array($roleModel->id, $cc['roles'])) {
                            return true;
                        }
                    }
                }
            }
        }

        // If it's a draft or pending signature, only the sender and signatories can view
        if ($this->status === 'draft' || $this->status === 'pending_signature') {
            return $this->sender_id === $user->id 
                || $this->signing_user_id === $user->id
                || $this->signatories()->where('user_id', $user->id)->exists();
        }

        // Department isolation & Strict Departmental Access check
        $isolation = \App\Models\Setting::query()
            ->where('setting_key', 'memo_settings.department_isolation')
            ->value('setting_value') === '1';

        $strictDeptAccess = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.strict_departmental_access')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if ($isolation || $strictDeptAccess) {
            $userDeptId = null;
            if ($user->admin) {
                $userDeptId = $user->admin->department_id;
            } elseif ($user->teacher) {
                $userDeptId = $user->teacher->department_id;
            } elseif ($user->nonTeachingStaff) {
                $userDeptId = $user->nonTeachingStaff->department_id;
            } elseif ($user->student) {
                $userDeptId = $user->student->department_id;
            }

            if ($userDeptId === null) {
                return false;
            }

            $senderDeptId = ($this->sender_entity_type === 'department') ? $this->sender_entity_id : null;
            $recipientDeptId = ($this->recipient_type === 'department') ? $this->recipient_entity_id : null;

            if ($senderDeptId !== null && (int)$userDeptId !== (int)$senderDeptId) {
                return false;
            }
            if ($recipientDeptId !== null && (int)$userDeptId !== (int)$recipientDeptId) {
                return false;
            }
        }

        // Users with view_all_memos permission can view everything (Auditors/Principals)
        if ($user->hasAdminPermission('view_all_memos')) {
            return true;
        }

        // Sender, legacy signing user, or assigned signatories can always view
        if ($this->sender_id === $user->id 
            || $this->signing_user_id === $user->id
            || $this->signatories()->where('user_id', $user->id)->exists()
        ) {
            return true;
        }

        // Time-based boundary check (Academic Session)
        $userSession = AcademicSession::query()
            ->where('start_date', '<=', $user->created_at)
            ->orderBy('start_date', 'desc')
            ->first();

        if ($userSession && $this->created_at < $userSession->start_date) {
            return false;
        }

        if ($this->readReceipts()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $this->resolveTargetRecipients()->contains('id', $user->id);
    }

    /**
     * Get the visible tracking logs based on thread isolation settings.
     */
    public function getVisibleTrackingLogs(User $user)
    {
        $isolation = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.thread_isolation_on_forward')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$isolation || $user->isAdminOwner() || $user->adminRoleSlug() === 'system_admin' || $this->sender_id === $user->id) {
            return $this->trackingLogs;
        }

        // Find the last tracking log where this user acted or was the recipient
        $lastLogUserInvolved = $this->trackingLogs()
            ->where(function ($q) use ($user) {
                $q->where('forwarded_by', $user->id)
                  ->orWhere('to_entity_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastLogUserInvolved) {
            return $this->trackingLogs()
                ->where('created_at', '<=', $lastLogUserInvolved->created_at)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return $this->trackingLogs;
    }

    /**
     * Resolve all CC recipient users for this memo.
     */
    public function resolveCCRecipients()
    {
        if (!$this->cc_recipients) {
            return collect();
        }

        $cc = $this->cc_recipients;
        $users = collect();

        if (isset($cc['users']) && !empty($cc['users'])) {
            $users = $users->merge(User::query()->where('active', true)->whereIn('id', $cc['users'])->get());
        }

        if (isset($cc['departments']) && !empty($cc['departments'])) {
            $users = $users->merge(User::query()->where('active', true)->where(function ($q) use ($cc) {
                $q->whereHas('admin', fn($a) => $a->whereIn('department_id', $cc['departments']))
                  ->orWhereHas('teacher', fn($t) => $t->whereIn('department_id', $cc['departments']))
                  ->orWhereHas('nonTeachingStaff', fn($ns) => $ns->whereIn('department_id', $cc['departments']));
            })->get());
        }

        if (isset($cc['roles']) && !empty($cc['roles'])) {
            $users = $users->merge(User::query()->where('active', true)->whereHas('admin', fn($a) => $a->whereIn('type', $cc['roles']))->get());
        }

        return $users->unique('id');
    }
}
