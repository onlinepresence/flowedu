<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'action',
        'description',
        'auditable_type',
        'auditable_id',
        'metadata',
        'is_flagged',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_flagged' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getActionDisplayNameAttribute(): string
    {
        return ucwords(str_replace(['_', '.'], ' ', $this->action));
    }

    public static function formatMetadataKey(string $key): string
    {
        $customMaps = [
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'othernames' => 'Other Names',
            'date_of_birth' => 'Date of Birth',
            'phone_number' => 'Phone Number',
            'contact_address' => 'Contact Address',
            'hall_id' => 'Hall',
            'department_id' => 'Department',
            'user_id' => 'User Account',
            'profile_pic' => 'Profile Picture',
            'admission_index' => 'Admission Index',
            'index_number' => 'Index Number',
            'approved' => 'Approval Status',
            'is_flagged' => 'Flagged',
            'active' => 'Active Status',
            'role_name' => 'Role Name',
            'display_name' => 'Display Name',
            'amount' => 'Amount',
            'invoice_number' => 'Invoice Number',
            'expense_number' => 'Expense Number',
            'gender' => 'Gender',
            'nationality' => 'Nationality',
            'recorded_by' => 'Recorded By',
            'created_by' => 'Created By',
            'staff_id' => 'Staff Member',
            'student_id' => 'Student',
            'status' => 'Status',
            'remarks' => 'Remarks',
            'items_count' => 'Number of Items',
            'vendor_name' => 'Vendor Name',
            'category' => 'Category',
            'date' => 'Date',
            'description' => 'Description',
        ];

        return $customMaps[$key] ?? ucwords(str_replace(['_', '.'], ' ', $key));
    }

    public static function formatMetadataValue(string $key, $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if ($value === null || $value === '') {
            return 'None';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        // Handle Foreign Key ID resolutions to be non-technical
        if (is_numeric($value)) {
            $id = (int) $value;
            
            if ($key === 'hall_id') {
                $hall = \App\Models\Hall::find($id);
                return $hall ? $hall->name : "Hall ID: {$id}";
            }
            if ($key === 'department_id') {
                $dept = \App\Models\Department::find($id);
                return $dept ? $dept->name : "Department ID: {$id}";
            }
            if (in_array($key, ['user_id', 'recorded_by', 'created_by', 'staff_id'], true)) {
                $user = \App\Models\User::find($id);
                return $user ? "{$user->name} ({$user->username})" : "User ID: {$id}";
            }
            if ($key === 'student_id') {
                $student = \App\Models\Student::find($id);
                return $student ? "{$student->firstname} {$student->lastname} ({$student->index_number})" : "Student ID: {$id}";
            }
        }

        return (string) $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
