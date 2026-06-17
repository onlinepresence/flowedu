<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoTracking extends Model
{
    protected $table = 'memo_tracking';

    public $timestamps = false;

    protected $fillable = [
        'memo_id',
        'from_entity_type',
        'from_entity_id',
        'to_entity_type',
        'to_entity_id',
        'forwarded_by',
        'action',
        'remarks',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }

    public function forwardedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_by');
    }

    public function getFromEntityNameAttribute(): string
    {
        return $this->resolveEntityName($this->from_entity_type, $this->from_entity_id);
    }

    public function getToEntityNameAttribute(): string
    {
        return $this->resolveEntityName($this->to_entity_type, $this->to_entity_id);
    }

    private function resolveEntityName(?string $type, ?int $id): string
    {
        if (! $type || ! $id) {
            return '—';
        }

        switch ($type) {
            case 'department':
                $dept = Department::query()->find($id);
                return $dept ? $dept->name . ' Department' : 'Unknown Department';
            case 'faculty':
                $faculty = Faculty::query()->find($id);
                return $faculty ? $faculty->name . ' Faculty' : 'Unknown Faculty';
            case 'user':
                $user = User::query()->find($id);
                return $user ? $user->name ?? $user->username : 'Unknown User';
            case 'role':
                $role = UserRole::query()->find($id);
                return $role ? $role->display_name : 'Unknown Role';
            default:
                return ucfirst($type);
        }
    }
}
