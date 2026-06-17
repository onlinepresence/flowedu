<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
