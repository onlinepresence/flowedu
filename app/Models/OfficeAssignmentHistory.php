<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeAssignmentHistory extends Model
{
    protected $fillable = [
        'admin_id',
        'role_id',
        'department_id',
        'faculty_id',
        'office',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }
}
