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
    ];

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

        // If it's a draft or pending signature, only the sender and signatories can view (already checked/authorized above)
        if ($this->status === 'draft' || $this->status === 'pending_signature') {
            return false;
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
}
