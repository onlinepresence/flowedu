<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffLeaveType extends Model
{
    protected $fillable = [
        'name',
        'max_leave_days',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
